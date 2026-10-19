import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { mkdtempSync, mkdirSync, writeFileSync, rmSync, existsSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
import { createServer } from 'node:net';
import { randomBytes } from 'node:crypto';

// All writes, credentials and uploaded files belong to a disposable test app.
// A cached Laravel configuration could override that isolation: refuse it.
assert.ok(!existsSync('bootstrap/cache/config.php'), 'Execute php artisan config:clear antes do teste de navegador.');
const temporary = mkdtempSync(join(tmpdir(), 'bonifica-browser-'));
const reports = resolve('storage/app/qa-reports', new Date().toISOString().replaceAll(':', '-'));
const socket = createServer();
await new Promise(resolve => socket.listen(0, '127.0.0.1', resolve));
const port = socket.address().port;
await new Promise(resolve => socket.close(resolve));
const base = `http://127.0.0.1:${port}`;
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_KEY: `base64:${randomBytes(32).toString('base64')}`, APP_URL: base, APP_TIMEZONE: 'UTC', CYCLE_TIMEZONE: 'America/Sao_Paulo', DB_CONNECTION: 'sqlite', DB_DATABASE: join(temporary, 'database.sqlite'), DB_URL: '', LARAVEL_STORAGE_PATH: join(temporary, 'storage'), SESSION_DRIVER: 'database', SESSION_SECURE_COOKIE: 'false', SESSION_DOMAIN: '', MAIL_MAILER: 'array', CACHE_STORE: 'array', QUEUE_CONNECTION: 'sync', IDEA_ATTACHMENTS_ENABLED: 'true', REGISTRATION_ENABLED: 'true', EMAIL_VERIFICATION_REQUIRED: 'false', ACCOUNT_INVITATION_DELIVERY: 'manual' };
for (const directory of ['framework/cache/data', 'framework/sessions', 'framework/views', 'logs', 'app/private']) mkdirSync(join(env.LARAVEL_STORAGE_PATH, directory), { recursive: true, mode: 0o700 });
writeFileSync(env.DB_DATABASE, '');
mkdirSync(reports, { recursive: true, mode: 0o700 });
let server, browser;
const errors = [];
const outcomes = [];
try {
    for (const command of [['migrate', '--force'], ['db:seed', '--force']]) {
        const result = spawnSync('php', ['artisan', ...command], { env, encoding: 'utf8' });
        assert.equal(result.status, 0, `Preparação falhou: ${result.stderr || result.stdout}`);
    }
    server = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${port}`, '--no-reload'], { env, stdio: 'ignore' });
    for (let attempt = 0; ; attempt++) {
        try { if ((await fetch(`${base}/login`)).ok) break; } catch {}
        assert.ok(attempt < 50 && server.exitCode === null, 'Servidor isolado não iniciou.');
        await new Promise(resolve => setTimeout(resolve, 200));
    }
    browser = await chromium.launch(process.env.QA_CHROME_EXECUTABLE ? { executablePath: process.env.QA_CHROME_EXECUTABLE } : { channel: 'chrome' });
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    page.on('dialog', dialog => dialog.accept());
    page.on('pageerror', error => errors.push(error.message));
    page.on('response', response => { if (response.status() >= 500) errors.push(`${response.status()} ${response.url()}`); });
    await page.goto(`${base}/login`);
    await page.locator('[name=email]').fill('admin@corp.local');
    await page.locator('[name=password]').fill('password');
    await Promise.all([page.waitForURL(url => url.pathname !== '/login'), page.locator('button[type=submit]').click()]);
    for (const dark of [false, true]) {
        await page.evaluate(dark => { localStorage.setItem('conted-theme', dark ? 'dark' : 'light'); }, dark);
        for (const route of ['/dashboard', '/admin', '/admin/users', '/admin/organization', '/admin/sectors', '/admin/sectors/create', '/admin/criteria', '/admin/criteria/create', '/admin/users/create', '/cycles', '/cycles/create', '/cycles/create?purpose=recognition', '/reports', '/profile', '/admin/privacy', '/audit']) {
            const response = await page.goto(base + route);
            assert.equal(response.status(), 200, route);
            const card = page.locator('.apple-card').first();
            if (await card.count()) {
                await card.hover();
                assert.equal(await card.evaluate(element => getComputedStyle(element).transform), 'none', `Cartão se move em ${route}`);
            }
        }
        await page.goto(`${base}/cycles`);
        assert.equal(await page.locator('form[data-cycle-purpose]').count(),0);
        await page.getByRole('link',{name:'Novo ciclo',exact:true}).click();
        assert.equal(new URL(page.url()).pathname,'/cycles/create');
        await page.goto(`${base}/dashboard`);
        assert.equal(await page.locator('.app-sidebar').evaluate(element => element.getBoundingClientRect().left),0);
        assert.ok(await page.locator('.app-content').evaluate(element => element.getBoundingClientRect().left >= 260));
        const link = page.locator('.app-nav-link:not([aria-current])').first();
        await page.mouse.move(0, 0);
        await link.hover();
        await page.waitForFunction(({dark}) => { const link = document.querySelector('.app-nav-link:not([aria-current])'); return getComputedStyle(link).backgroundColor === (dark ? 'rgb(51, 65, 85)' : 'rgb(232, 238, 244)'); }, {dark});
        assert.equal(await link.evaluate(element => getComputedStyle(element).backgroundColor), dark ? 'rgb(51, 65, 85)' : 'rgb(232, 238, 244)');
        const statCard=page.locator('.stat-card').first();
        const actualValue=await statCard.locator('.stat-card-value').innerText();
        await statCard.hover();
        await page.waitForFunction(()=>new DOMMatrix(getComputedStyle(document.querySelector('.stat-card')).transform).m42 < -4.9);
        await page.mouse.move(0,0);
        await page.waitForFunction(()=>Math.abs(new DOMMatrix(getComputedStyle(document.querySelector('.stat-card')).transform).m42)<0.01);
        await page.emulateMedia({reducedMotion:'reduce'});
        await statCard.hover();
        assert.equal(await statCard.evaluate(element=>getComputedStyle(element).transform),'none');
        assert.equal(await statCard.evaluate(element=>getComputedStyle(element).animationName),'none');
        assert.equal(await statCard.locator('.stat-card-value').innerText(),actualValue);
        await page.emulateMedia({reducedMotion:'no-preference'});
        await page.mouse.move(0,0);
        await page.waitForFunction(()=>Array.from(document.querySelectorAll('.stat-card')).every(element=>getComputedStyle(element).opacity==='1'));
        await page.screenshot({ path: join(reports, dark ? 'desktop-escuro.png' : 'desktop-claro.png'), fullPage: true });
        outcomes.push(`Indicadores: hover de 5 px, retorno à posição original e redução de movimento: ${dark ? 'escuro' : 'claro'}`);
        outcomes.push(`Telas administrativas, perfil, indicadores e hovers: ${dark ? 'escuro' : 'claro'}`);
    }
    // Exercise the email-free first access with a separate employee browser session.
    await page.goto(`${base}/admin/users/create`);
    await page.locator('main [name=name]').fill('Pessoa QA primeiro acesso');
    await page.locator('main [name=email]').fill('primeiro-acesso@qa.test');
    await Promise.all([page.waitForNavigation(), page.getByRole('button', {name:'Cadastrar pessoa', exact:true}).click()]);
    const invitedUserUrl = page.url();
    await page.getByRole('link', {name:'Confirmar minha senha para gerar link', exact:true}).click();
    await page.locator('[name=password]').fill('password');
    await Promise.all([page.waitForNavigation(), page.getByRole('button', {name:'Confirmar', exact:true}).click()]);
    assert.equal(page.url(), invitedUserUrl);
    await page.locator('form[action$="/access-link"] [name=reason]').fill('Titular fictício presente para validar primeiro acesso.');
    await Promise.all([page.waitForNavigation(), page.getByRole('button', {name:'Gerar link de primeiro acesso', exact:true}).click()]);
    const individualLink = await page.locator('#individual-access-link').inputValue();
    const invitedContext = await browser.newContext();
    const invitedPage = await invitedContext.newPage();
    invitedPage.on('pageerror', error => errors.push(error.message));
    await invitedPage.goto(individualLink);
    await invitedPage.locator('[name=password]').fill('SenhaQAIndividual123');
    await invitedPage.locator('[name=password_confirmation]').fill('SenhaQAIndividual123');
    await Promise.all([invitedPage.waitForNavigation(), invitedPage.locator('button[type=submit]').click()]);
    assert.equal(new URL(invitedPage.url()).pathname, '/login');
    await invitedPage.locator('[name=email]').fill('primeiro-acesso@qa.test');
    await invitedPage.locator('[name=password]').fill('SenhaQAIndividual123');
    await Promise.all([invitedPage.waitForNavigation(), invitedPage.locator('button[type=submit]').click()]);
    await invitedPage.goto(`${base}/ideas/create`);
    await invitedPage.getByText('Passo 1 de 3.', {exact:false}).waitFor();
    assert.equal((await invitedPage.goto(`${base}/admin/users`)).status(), 403);
    await invitedContext.close();
    // Never save screenshots or logs containing the individual link.
    await page.goto(invitedUserUrl);
    await page.reload();
    assert.equal(await page.locator('#individual-access-link').count(),0);
    await page.getByText('Senha de acesso definida.', {exact:false}).waitFor();
    outcomes.push('Primeiro acesso manual: cadastro, confirmação do administrador, link privado, definição de senha e login sem e-mail; funcionário sem acesso administrativo.');
    // Start a fresh admin session so document review still exercises its own password confirmation.
    await page.context().clearCookies();
    await page.goto(`${base}/login`);
    await page.locator('[name=email]').fill('admin@corp.local');
    await page.locator('[name=password]').fill('password');
    await Promise.all([page.waitForNavigation(), page.locator('button[type=submit]').click()]);
    await page.goto(`${base}/admin`);
    assert.equal(await page.locator('main form[action$="/evaluation-criteria"]').count(),0);
    await page.locator('main a[href$="/admin/sectors"]').click();
    await page.getByRole('link',{name:'Novo setor',exact:true}).click();
    await page.locator('main input[name=name]').fill('Setor QA organização');
    await page.locator('main textarea[name=description]').fill('Setor fictício para validar a organização.');
    await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Salvar setor',exact:true}).click()]);
    const sectorUrl=page.url();
    assert.match(sectorUrl,/\/admin\/sectors\/\d+$/);
    await page.locator('select[name=membership]').selectOption('unlinked');
    await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Pesquisar',exact:true}).click()]);
    const employeeRow=page.getByRole('row').filter({hasText:'funcionario@corp.local'});
    await Promise.all([page.waitForNavigation(),employeeRow.getByRole('button',{name:/^Vincular /}).click()]);
    await page.goto(sectorUrl);
    await page.getByRole('row').filter({hasText:'funcionario@corp.local'}).waitFor();
    await page.screenshot({path:join(reports,'setor-pessoas-vinculadas.png'),fullPage:true});
    await Promise.all([page.waitForNavigation(),page.getByRole('row').filter({hasText:'funcionario@corp.local'}).getByRole('button',{name:/^Desvincular /}).click()]);
    assert.equal(await page.getByRole('row').filter({hasText:'funcionario@corp.local'}).count(),0);
    await page.getByRole('link',{name:'Editar setor',exact:true}).click();
    await page.locator('input[type=checkbox][name=is_active]').uncheck();
    await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Salvar setor',exact:true}).click()]);
    await page.getByText('Setor inativo: novos vínculos estão bloqueados. Os vínculos existentes permanecem disponíveis para consulta ou remoção.',{exact:true}).waitFor();
    await page.goto(`${base}/admin/sectors?status=inactive`);
    await page.getByRole('row').filter({hasText:'Setor QA organização'}).waitFor();
    await page.goto(`${base}/admin/criteria`);
    assert.equal(await page.locator('main form[action$="/evaluation-criteria"]').count(),0);
    await page.getByRole('link',{name:'Novo critério técnico',exact:true}).click();
    await page.locator('main [name=name]').fill('Critério QA da administração');
    await page.locator('main [name=description]').fill('Avalie a proposta de um a cinco.');
    await page.locator('main [name=weight]').fill('50');
    await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Cadastrar critério',exact:true}).click()]);
    assert.equal(new URL(page.url()).pathname,'/admin/criteria');
    await page.getByRole('row').filter({hasText:'Critério QA da administração'}).getByRole('link',{name:/Editar critério/}).click();
    await page.locator('[name=active]').selectOption('0');
    await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Salvar critério',exact:true}).click()]);
    await page.goto(`${base}/admin/criteria?status=inactive`);
    await page.getByRole('row').filter({hasText:'Critério QA da administração'}).waitFor();
    await page.screenshot({path:join(reports,'criterios-listagem.png'),fullPage:true});
    outcomes.push('Administração: áreas separadas, setor com vínculos/remoção/inativação, critério com criação/edição/inativação');
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(`${base}/dashboard`);
    assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth),'Painel com rolagem horizontal no celular.');
    await page.waitForFunction(()=>Array.from(document.querySelectorAll('.stat-card')).every(element=>getComputedStyle(element).opacity==='1'));
    await page.screenshot({path:join(reports,'painel-celular-escuro.png'),fullPage:true});
    await page.goto(`${base}/ideas/create`);
    await page.getByRole('button', { name: 'Continuar', exact: true }).click();
    assert.ok(await page.locator('[data-step="1"]').isVisible());
    for (const [name, value] of Object.entries({ title: 'QA navegador: melhoria do atendimento', problem_description: 'Demora no atendimento.', solution_description: 'Organizar a fila de solicitações.' })) await page.locator(`[name=${name}]`).fill(value);
    await page.getByRole('button', { name: 'Continuar', exact: true }).click();
    await page.locator('[data-step="2"]').waitFor({ state: 'visible' });
    await page.locator('[name=origin_sector_id]').selectOption({ index: 1 });
    await page.locator('[name="beneficiary_sectors[]"]').first().check();
    await page.getByRole('button', { name: 'Continuar', exact: true }).click();
    await page.locator('[data-step="3"]').waitFor({ state: 'visible' });
    await page.locator('[name=expected_results]').fill('Redução de tempo no atendimento.');
    await page.locator('[name=resources_needed]').fill('Uma planilha compartilhada.');
    await Promise.all([page.waitForNavigation(), page.getByRole('button', { name: 'Conferir minha ideia', exact: true }).click()]);
    await page.getByRole('heading', { name: 'Está tudo certo com sua ideia?', exact: true }).waitFor();
    await Promise.all([page.waitForNavigation(), page.getByRole('button', { name: 'Enviar minha ideia', exact: true }).click()]);
    assert.match(page.url(), /\/ideas\/\d+$/);
    await page.screenshot({ path: join(reports, 'ideia-celular.png'), fullPage: true });
    await page.getByRole('button', { name: 'Abrir menu' }).click();
    assert.ok(await page.locator('#mobile-navigation').isVisible());
    await page.keyboard.press('Tab');
    assert.ok(await page.evaluate(() => document.activeElement.tagName !== 'BODY'));
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), 'Rolagem horizontal no celular.');
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('[aria-label="Abrir menu"]').getAttribute('aria-expanded'),'false');
    assert.ok(await page.locator('[aria-label="Abrir menu"]').evaluate(element=>element===document.activeElement));
    assert.equal(await page.locator('.app-content').getAttribute('inert'),null);
    outcomes.push('Celular: etapas, validação, revisão, envio, menu lateral, teclado e fechamento com Escape');
    await page.locator('input[name=document]').setInputFiles({ name: 'evidencia-qa.txt', mimeType: 'text/plain', buffer: Buffer.from('Evidência fictícia para validar a revisão privada.') });
    await Promise.all([page.waitForNavigation(), page.getByRole('button', { name: 'Enviar documento privado', exact: true }).click()]);
    await page.getByText('Revisar acesso, retenção ou exclusão', { exact: true }).click();
    await Promise.all([page.waitForNavigation(), page.getByRole('link', { name: 'Confirmar senha para revisar documento', exact: true }).click()]);
    await page.locator('input[name=password]').fill('password');
    await Promise.all([page.waitForNavigation(), page.locator('button[type=submit]').click()]);
    for (const action of ['restrict', 'delete']) {
        await page.getByText('Revisar acesso, retenção ou exclusão', { exact: true }).click();
        const form = page.locator('form[action$="/review"]').filter({ has: page.locator('select[name=action]') });
        await form.locator('select[name=action]').selectOption(action);
        await form.locator('[name=notes]').fill('Documento fictício revisado e autorizado para o teste de privacidade.');
        await form.locator('[name=review_confirmed]').check();
        await Promise.all([page.waitForNavigation(), form.getByRole('button').click()]);
        await page.getByText(action === 'restrict' ? 'Restrito à revisão administrativa' : 'Arquivo excluído definitivamente', { exact: true }).waitFor();
    }
    outcomes.push('Documento privado: envio, confirmação de senha, restrição e exclusão definitiva');
    await page.goto(`${base}/dashboard`);
    const notification = page.locator('.notification-card').first();
    await notification.waitFor();
    assert.equal(await notification.evaluate(element => getComputedStyle(element).backgroundColor), 'rgb(30, 41, 59)');
    await page.screenshot({ path: join(reports, 'avisos-escuro.png'), fullPage: true });
    outcomes.push('Painel com avisos preenchidos: contraste no tema escuro');
    await page.setViewportSize({width: 1440, height: 1000});
    // A second person submits the proposal; the administrator cannot select their own idea.
    const employeePage = await browser.newPage({ viewport: {width: 390, height: 844},hasTouch:true,isMobile:true });
    const voterPage = await browser.newPage();
    for (const [target,email] of [[employeePage,'funcionario@corp.local'],[voterPage,'avaliador@corp.local']]) {
        target.on('pageerror', error => errors.push(error.message));
        target.on('response', response => { if(response.status() >= 500) errors.push(`${response.status()} ${response.url()}`); });
        await target.goto(`${base}/login`);
        await target.locator('[name=email]').fill(email);
        await target.locator('[name=password]').fill('password');
        await Promise.all([target.waitForURL(url => url.pathname !== '/login'), target.locator('button[type=submit]').click()]);
    }
    assert.equal(await employeePage.evaluate(()=>matchMedia('(hover: hover)').matches),false);
    assert.equal(await employeePage.locator('.stat-card').first().evaluate(element=>getComputedStyle(element).transform),'none');
    outcomes.push('Toque: indicadores sem deslocamento por hover em um contexto móvel');
    await employeePage.goto(`${base}/ideas/create`);
    for (const [name,value] of Object.entries({ title:'Proposta antes da execução', problem_description:'Uma atividade demora demais.', solution_description:'Simplificar o atendimento.' })) await employeePage.locator(`[name=${name}]`).fill(value);
    await employeePage.getByRole('button',{name:'Continuar',exact:true}).click();
    await employeePage.locator('[name=origin_sector_id]').selectOption({index:1});
    await employeePage.locator('[name="beneficiary_sectors[]"]').first().check();
    await employeePage.getByRole('button',{name:'Continuar',exact:true}).click();
    // The two estimation fields intentionally remain empty.
    await Promise.all([employeePage.waitForNavigation(),employeePage.getByRole('button',{name:'Conferir minha ideia',exact:true}).click()]);
    await Promise.all([employeePage.waitForNavigation(),employeePage.getByRole('button',{name:'Enviar minha ideia',exact:true}).click()]);
    const proposalUrl = employeePage.url();
    const proposalId = new URL(proposalUrl).pathname.split('/').pop();
    await page.goto(proposalUrl);
    const publication = page.locator('form[action$="/review"]');
    await publication.locator('[name=notes]').fill('Proposta compreensível e sem duplicação identificada.');
    await publication.locator('[name=privacy_reviewed]').check();
    await Promise.all([page.waitForNavigation(),publication.getByRole('button',{name:'Liberar para votação',exact:true}).click()]);
    await page.getByText('Ideia liberada para votação. Inclua em um ciclo; a execução será decidida depois.',{exact:true}).waitFor();
    await page.goto(`${base}/cycles`);
    await page.getByRole('link',{name:'Novo ciclo',exact:true}).click();
    assert.equal(new URL(page.url()).pathname,'/cycles/create');
    const localTime = timestamp => {
        const parts = Object.fromEntries(new Intl.DateTimeFormat('en-CA', {timeZone: 'America/Sao_Paulo', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23'}).formatToParts(new Date(timestamp)).map(part => [part.type, part.value]));
        return `${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}`;
    };
    const start = localTime(Date.now() + 5 * 60 * 1000);
    const end = localTime(Date.now() + 10 * 60 * 1000);
    const cycleForm = page.locator('form[data-cycle-purpose=selection]');
    for (const [name, value] of Object.entries({name: 'Ciclo curto de QA', starts_at: start, ends_at: end})) await cycleForm.locator(`[name="${name}"]`).fill(value);
    await Promise.all([page.waitForNavigation(), cycleForm.getByRole('button', {name: 'Continuar para escolher as propostas', exact: true}).click()]);
    assert.match(page.url(), /\/cycles\/\d+$/);
    assert.equal(await page.locator('input[name=starts_at]').inputValue(), start);
    assert.equal(await page.locator('input[name=ends_at]').inputValue(), end);
    await page.screenshot({ path: join(reports, 'ciclo-curto-brasilia.png'), fullPage: true });
    outcomes.push('Ciclo curto: horário de Brasília convertido e reexibido corretamente com servidor em UTC');
    const cycleId = new URL(page.url()).pathname.split('/').pop();
    await page.locator(`input[name="ideas[]"][value="${proposalId}"]`).check();
    await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Confirmar e programar votação',exact:true}).click()]);
    await page.goto(`${base}/cycles/create`);
    const anotherForm=page.locator('form[data-cycle-purpose=selection]');
    for(const [name,value] of Object.entries({name:'Ciclo concorrente de QA',starts_at:start,ends_at:end})) await anotherForm.locator(`[name="${name}"]`).fill(value);
    await Promise.all([page.waitForNavigation(),anotherForm.getByRole('button',{name:'Continuar para escolher as propostas',exact:true}).click()]);
    const otherCycleUrl=page.url();
    assert.ok(await page.locator(`input[name="ideas[]"][value="${proposalId}"]`).isDisabled());
    await page.getByText('Esta ideia já participa de uma votação aberta ou programada. Conclua esse ciclo primeiro.',{exact:true}).waitFor();
    await page.screenshot({path:join(reports,'proposta-indisponivel.png'),fullPage:true});
    await page.goto(`${base}/cycles/${cycleId}`);
    // Advance only the disposable fixture's clock boundaries, avoiding a real ten-minute wait.
    const fixtureTime = phase => {
        const php = `require 'vendor/autoload.php'; $app = require 'bootstrap/app.php'; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); Illuminate\\Support\\Facades\\DB::table('voting_cycles')->where('id', (int)$argv[1])->update($argv[2] === 'vote' ? ['starts_at'=>now()->subMinute(), 'ends_at'=>now()->addMinute()] : ['ends_at'=>now()->subSecond()]);`;
        const result = spawnSync('php',['-r',php,cycleId,phase],{env,encoding:'utf8'});
        assert.equal(result.status,0,result.stderr);
    };
    fixtureTime('vote');
    await voterPage.goto(`${base}/ideas/${proposalId}/voting/${cycleId}`);
    await voterPage.getByText('Esta ideia ainda não foi implementada. Avalie o benefício esperado.',{exact:true}).waitFor();
    for (const dark of [false,true]) {
        await voterPage.evaluate(dark=>localStorage.setItem('conted-theme',dark?'dark':'light'),dark);
        await voterPage.reload();
        assert.equal(await voterPage.locator('.vote-choice input:checked').count(),0,'Nenhuma nota deve vir marcada.');
        const firstCriterion=voterPage.locator('fieldset').first();
        const moderate=firstCriterion.locator('input[value="3"]');
        await moderate.check();
        const selectedChoice=moderate.locator('..');
        assert.equal(await selectedChoice.evaluate(element=>getComputedStyle(element).animationName),'vote-choice-confirm');
        await voterPage.waitForFunction(()=>Array.from(document.querySelectorAll('.vote-field')).every(element=>getComputedStyle(element).opacity==='1'));
        await voterPage.waitForFunction(dark=>getComputedStyle(document.querySelector('.vote-choice:has(input:checked)')).backgroundColor===(dark?'rgb(12, 74, 110)':'rgb(224, 242, 254)'),dark);
        assert.equal(await selectedChoice.evaluate(element=>getComputedStyle(element).backgroundColor),dark?'rgb(12, 74, 110)':'rgb(224, 242, 254)');
        await voterPage.keyboard.press('ArrowRight');
        assert.equal(await firstCriterion.locator('input[value="4"]').isChecked(),true);
        assert.equal(await firstCriterion.locator('input[value="4"]').locator('..').evaluate(element=>getComputedStyle(element).outlineWidth),'3px');
        await voterPage.emulateMedia({reducedMotion:'reduce'});
        await moderate.check();
        assert.equal(await selectedChoice.evaluate(element=>getComputedStyle(element).animationName),'none');
        assert.equal(await selectedChoice.evaluate(element=>getComputedStyle(element).transitionDuration),'0s');
        assert.equal(await selectedChoice.evaluate(element=>getComputedStyle(element).transform),'none');
        await voterPage.screenshot({path:join(reports,dark?'votacao-notas-escuro.png':'votacao-notas-claro.png'),fullPage:true});
        await voterPage.emulateMedia({reducedMotion:'no-preference'});
    }
    await voterPage.setViewportSize({width:390,height:844});
    await voterPage.reload();
    await voterPage.locator('fieldset').first().locator('input[value="3"]').check();
    assert.ok(await voterPage.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth));
    await voterPage.screenshot({path:join(reports,'votacao-notas-celular.png'),fullPage:true});
    outcomes.push('Notas: destaque e animação uniformes nos dois temas, teclado por setas, nenhuma nota inicial, redução de movimento e layout de celular.');
    for (const field of await voterPage.locator('fieldset').all()) await field.locator('input[value="5"]').check();
    await voterPage.getByRole('button',{name:'Conferir minhas respostas',exact:true}).click();
    await Promise.all([voterPage.waitForNavigation(),voterPage.getByRole('button',{name:'Confirmar meu voto',exact:true}).click()]);
    await voterPage.getByText('Sua participação',{exact:true}).waitFor();
    fixtureTime('close');
    await page.reload();
    await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Encerrar e apurar após o prazo',exact:true}).click()]);
    await page.goto(otherCycleUrl);
    assert.ok(await page.locator(`input[name="ideas[]"][value="${proposalId}"]`).isDisabled());
    await page.getByText('Esta ideia aguarda a decisão da equipe no ciclo anterior. Registre a decisão antes de incluí-la novamente.',{exact:true}).waitFor();
    await page.goto(`${base}/cycles/${cycleId}`);
    await page.locator(`.result-details-link[href="#cycle-entry-${proposalId}"]`).click();
    const decision = page.locator('form[action$="/decision"]');
    await decision.locator('[name=decision]').selectOption('selected');
    await decision.locator('[name=notes]').fill('Ideia viável e recursos disponíveis para iniciar execução.');
    await Promise.all([page.waitForNavigation(),decision.getByRole('button').click()]);
    await page.getByText('Selecionada para avançar',{exact:true}).first().waitFor();
    await page.locator(`.result-details-link[href="#cycle-entry-${proposalId}"]`).click();
    await page.screenshot({path:join(reports,'selecao-antes-implementacao.png'),fullPage:true});
    await page.getByRole('link',{name:'Acompanhar a ideia e definir a execução',exact:true}).click();
    await page.getByRole('heading',{name:'Plano de implementação',exact:true}).waitFor();
    await page.goto(otherCycleUrl);
    assert.equal(await page.locator(`input[name="ideas[]"][value="${proposalId}"]`).isDisabled(),false);
    outcomes.push('Propostas: opção bloqueada durante outra votação e enquanto há decisão pendente; liberada após decisão');

    outcomes.push('Seleção: registro rápido sem estimativas, liberação direta sem triagem/notas técnicas, abertura, voto de outra pessoa, apuração e decisão para executar');
    for (const dark of [false,true]) {
        await page.evaluate(dark=>localStorage.setItem('conted-theme',dark?'dark':'light'),dark);
        await page.goto(`${base}/reports?cycle=${cycleId}`);
        await page.getByRole('heading',{name:'Relatório por ciclo',exact:true}).waitFor();
        await page.getByRole('heading',{name:'Classificação das ideias',exact:true}).waitFor();
        await page.getByRole('cell',{name:'100,00/100',exact:false}).waitFor();
        assert.equal(await page.locator('.app-nav-link').filter({hasText:'Auditoria'}).count(),0);
        await page.emulateMedia({reducedMotion:'reduce'});
        assert.equal(await page.locator('.result-score-fill').first().evaluate(element=>getComputedStyle(element).animationName),'none');
        await page.screenshot({path:join(reports,dark?'relatorio-ciclo-escuro.png':'relatorio-ciclo-claro.png'),fullPage:true});
        await page.emulateMedia({reducedMotion:'no-preference'});
        await page.getByRole('link',{name:/Ver detalhes da ideia/}).first().click();
        await page.waitForFunction(id=>document.getElementById(`cycle-entry-${id}`)?.open,proposalId);
    }
    for (const width of [320,390]) {
        await page.setViewportSize({width,height:844});
        await page.goto(`${base}/reports?cycle=${cycleId}`);
        assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth));
        await page.goto(`${base}/cycles/${cycleId}`);
        assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth));
    }
    await page.setViewportSize({width:1440,height:1000});
    outcomes.push('Relatório por ciclo: classificação, decisão e bonificação separadas, detalhes acessíveis, dois temas, movimento reduzido e largura de 320/390 px.');





    await page.goto(`${base}/reports?view=general`);
    const chartLine=page.locator('.chart-line').first();
    await page.locator('.metric-chart').first().waitFor();
    await chartLine.waitFor({state:'attached'});
    await page.emulateMedia({reducedMotion:'reduce'});
    assert.equal(await chartLine.evaluate(element=>getComputedStyle(element).animationName),'none');
    assert.equal(await chartLine.evaluate(element=>getComputedStyle(element).strokeDashoffset),'0px');
    await page.emulateMedia({reducedMotion:'no-preference'});
    outcomes.push('Gráficos: desenho desativado e linha completa com redução de movimento');
    await page.goto(`${base}/cycles`);
    await page.getByRole('row').filter({hasText:'Ciclo curto de QA'}).waitFor();
    assert.equal(await page.locator('form[data-cycle-purpose]').count(),0);
    await page.screenshot({path:join(reports,'listagem-ciclos.png'),fullPage:true});
    for(const width of [320,390]) {
        await page.setViewportSize({width,height:844});
        for(const route of ['/dashboard','/cycles','/cycles/create','/ideas','/admin','/admin/sectors','/admin/criteria','/admin/users']) {
            await page.goto(base+route);
            assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth),`Rolagem da página em ${width}px: ${route}`);
        }
        await page.getByRole('button',{name:'Abrir menu',exact:true}).click();
        await page.waitForFunction(()=>document.activeElement?.classList.contains('sidebar-close'));
        assert.equal(await page.locator('.app-topbar').evaluate(element=>element.inert),true);
        assert.equal(await page.locator('.app-content').evaluate(element=>element.inert),true);
        await page.screenshot({path:join(reports,`menu-celular-${width}.png`),fullPage:true});
        await page.locator('.sidebar-brand a').focus();
        await page.keyboard.press('Shift+Tab');
        assert.ok(await page.locator('.sidebar-footer button[type=submit]').evaluate(element=>element===document.activeElement));
        await page.keyboard.press('Tab');
        assert.ok(await page.locator('.sidebar-brand a').evaluate(element=>element===document.activeElement));
        await page.keyboard.press('Escape');
        await page.waitForFunction(()=>document.activeElement?.getAttribute('aria-label')==='Abrir menu');
    }
    outcomes.push('Listagem separada da criação, navegação lateral e ausência de rolagem da página em 320 e 390 px');
    // Resizing must never leave keyboard focus on a control that just became hidden.
    await page.setViewportSize({width:1024,height:844});
    await page.waitForFunction(()=>document.activeElement?.matches('.sidebar-link[aria-current="page"]'));
    await page.setViewportSize({width:1023,height:844});
    await page.waitForFunction(()=>document.activeElement?.getAttribute('aria-label')==='Abrir menu');
    await page.getByRole('button',{name:'Abrir menu',exact:true}).click();
    await page.waitForFunction(()=>document.activeElement?.classList.contains('sidebar-close'));
    await page.setViewportSize({width:1024,height:844});
    await page.waitForFunction(()=>document.activeElement?.matches('.sidebar-link[aria-current="page"]'));
    assert.equal(await page.evaluate(()=>document.documentElement.style.overflow),'');
    assert.equal(await page.locator('.app-topbar').getAttribute('inert'),null);
    await page.setViewportSize({width:390,height:844});
    await page.getByRole('button',{name:'Abrir menu',exact:true}).click();
    await page.waitForFunction(()=>document.activeElement?.classList.contains('sidebar-close'));
    await page.locator('.sidebar-overlay').click({position:{x:380,y:300}});
    await page.waitForFunction(()=>document.activeElement?.getAttribute('aria-label')==='Abrir menu');
    outcomes.push('Menu: fundo indisponível, fechamento pelo fundo e foco preservado ao alternar entre 1023 e 1024 px');
    await page.goto(`${base}/cycles/create`);
    const invalidForm=page.locator('form[data-cycle-purpose=selection]');
    const invalidStart=localTime(Date.now()+15*60*1000), invalidEnd=localTime(Date.now()+10*60*1000);
    for(const [name,value] of Object.entries({name:'QA correção de período',starts_at:invalidStart,ends_at:invalidEnd})) await invalidForm.locator(`[name="${name}"]`).fill(value);
    await Promise.all([page.waitForNavigation(),invalidForm.getByRole('button',{name:'Continuar para escolher as propostas',exact:true}).click()]);
    assert.equal(new URL(page.url()).pathname,'/cycles/create');
    assert.equal(await page.locator('[name=name]').inputValue(),'QA correção de período');
    assert.equal(await page.locator('[name=starts_at]').inputValue(),invalidStart);
    assert.equal(await page.locator('[name=ends_at]').inputValue(),invalidEnd);
    assert.ok(await page.locator('[role=alert]').first().isVisible());
    await page.getByRole('link',{name:'← Voltar para os ciclos',exact:true}).click();
    assert.equal(new URL(page.url()).pathname,'/cycles');
    assert.equal(await page.locator('form[data-cycle-purpose]').count(),0);
    outcomes.push('Criação: erro de período retorna ao formulário com valores preservados e permite voltar à listagem');

    await page.goto(`${base}/ideas?search=QA%20navegador`);
    const deletionDialog = page.getByRole('dialog', {name:'Excluir ideias', exact:true});
    for (const dark of [false,true]) {
        await page.evaluate(dark=>{localStorage.setItem('conted-theme',dark?'dark':'light');},dark);
        await page.reload();
        await page.getByRole('button',{name:'Excluir ideias',exact:true}).click();
        await deletionDialog.waitFor();
        assert.equal(await deletionDialog.evaluate(element=>getComputedStyle(element).backgroundColor),dark?'rgb(30, 41, 59)':'rgb(255, 255, 255)');
        assert.ok(await deletionDialog.evaluate(element=>element.contains(document.activeElement)));
        await page.screenshot({path:join(reports,dark?'excluir-ideias-escuro.png':'excluir-ideias-claro.png'),fullPage:true});
        await page.keyboard.press('Escape');
        assert.equal(await deletionDialog.isVisible(),false);
        assert.equal(await page.getByRole('button',{name:'Excluir ideias',exact:true}).evaluate(element=>element===document.activeElement),true);
    }
    await page.getByRole('button',{name:'Excluir ideias',exact:true}).click();
    await deletionDialog.locator('input[name="ids[]"]:enabled').first().check();
    await deletionDialog.getByRole('button',{name:'Continuar',exact:true}).click();
    await deletionDialog.locator('[name=deletion_reason]').fill('Cadastro fictício removido após a validação de QA.');
    await deletionDialog.locator('[name=confirm_deletion]').check();
    await Promise.all([page.waitForNavigation(),deletionDialog.getByRole('button',{name:'Confirmar exclusão',exact:true}).click()]);
    await page.getByText('1 ideia(s) excluída(s) das listagens. O histórico foi preservado.',{exact:true}).waitFor();
    await page.goto(`${base}/ideas?search=QA%20navegador`);
    await page.getByText('Nenhuma ideia por aqui ainda',{exact:true}).waitFor();
    outcomes.push('Exclusão: modal claro/escuro em celular, foco e Escape, seleção, confirmação e remoção das listagens.');

    assert.deepEqual(errors, []);
    writeFileSync(join(reports, 'resultado.json'), JSON.stringify({ status: 'passed', outcomes, errors }, null, 2));
    console.log(outcomes.join('\n'));
    console.log(`Evidências: ${reports}`);
} catch (error) {
    writeFileSync(join(reports, 'resultado.json'), JSON.stringify({ status: 'failed', outcomes, errors: [...errors, error.message] }, null, 2));
    throw error;
} finally {
    await browser?.close();
    if (server && server.exitCode === null) {
        server.kill('SIGTERM');
        await new Promise(resolve => { server.once('exit', resolve); setTimeout(resolve, 2000); });
    }
    rmSync(temporary, { recursive: true, force: true });
}
