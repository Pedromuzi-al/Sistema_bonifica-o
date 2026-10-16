# Plataforma Corporativa de Ideias

Aplicação Laravel para registrar propostas de melhoria, acompanhar sua avaliação e reconhecer contribuições com impacto comprovado. A autoria é privada para os demais funcionários; usuários autorizados da equipe de avaliação e administração podem consultá-la.

## Arquitetura

- **Backend:** PHP 8.3 ou superior, Laravel 13 e Eloquent.
- **Banco de dados:** MySQL em execução normal; SQLite em memória nos testes.
- **Autorização:** Policies do Laravel e papéis/permissões de `spatie/laravel-permission`.
- **Interface:** Blade, Tailwind CSS, Alpine.js e Vite.
- **Testes:** PHPUnit por meio de `php artisan test`.

O código está organizado pelas convenções do Laravel: models em `app/Models`, controllers e requests HTTP em `app/Http`, autorização em `app/Policies`, regras reutilizáveis em `app/Services`, migrations em `database/migrations` e telas Blade em `resources/views`.

## Fluxo previsto

1. O funcionário autenticado registra uma ideia; a autoria vem da sessão, não dos dados enviados pelo navegador.
2. A equipe de avaliação faz a triagem, registra pareceres e decide se a proposta pode avançar.
3. A equipe acompanha a implementação e registra resultados reais, evidências e limitações.
4. Uma versão revisada e sem identificação do autor pode ser publicada para os funcionários.
5. Um ciclo de votação coleta avaliações anônimas e únicas por funcionário, ideia e ciclo.
6. Resultados comprovados, avaliação técnica e votação podem compor o reconhecimento, segundo regras definidas antes do ciclo.
7. Usuários autorizados registram e aprovam bonificações; mudanças relevantes devem permanecer auditáveis.

## Dados principais

As migrations e models já incluem usuários, setores, ideias e setores beneficiados, histórico de status, avaliações e critérios, planos de implementação, resultados, ciclos e critérios de votação, votos e notas, regras e concessões de bonificação, notificações, auditoria e tabelas de papéis/permissões.

Relacionamentos previstos: uma ideia pertence a um autor e a um setor de origem; pode beneficiar vários setores; mantém histórico e avaliações; possui registros de implementação e resultado; pode receber votos em ciclos diferentes; e pode estar associada a reconhecimentos.

## Privacidade e acesso

- Funcionários podem abrir as próprias ideias e ideias públicas; ideias privadas de outros autores retornam acesso negado.
- As listagens de funcionários incluem apenas ideias próprias ou públicas, sem carregar a relação de autoria.
- A identidade do autor é exibida somente ao próprio autor e a usuários autorizados pela policy.
- O painel de avaliação e o painel administrativo exigem autorização no servidor; esconder links na interface não substitui essa verificação.
- Registros de votação não devem expor a relação entre votante e ideia para outros funcionários.

O anonimato é perante os demais colaboradores. Avaliadores e administradores só consultam autoria quando recebem a permissão explícita `view_author_identity`. Antes de produção, a empresa ainda deve definir finalidade, retenção, acesso e procedimentos de atendimento aos direitos previstos na LGPD.

## Fluxos implementados

- Cadastro validado por Form Request, com autoria obtida da sessão, setores distintos e gravação transacional de proposta, histórico, auditoria e notificação.
- Etapas controladas: envio → triagem → avaliação → aprovação → implementação → validação → conclusão → votação → encerramento → finalização. Solicitações de informações e rejeições exigem justificativa; o autor pode responder privadamente.
- Pareceres técnicos com critérios configuráveis, notas de 1 a 5 e histórico de avaliações. Aprovação exige parecer favorável; execução exige plano, responsável e prazo.
- Indicadores flexíveis com valores anterior/posterior, unidade, período, evidência, descrição qualitativa e limitações. Diferenças e percentuais são calculados quando há dados; edição invalida a validação anterior.
- Publicação revisada: funcionários recebem somente textos públicos aprovados, sem o conteúdo interno da proposta. A permissão `view_author_identity` é independente da permissão de avaliar.
- Ciclos configurados e abertos antes do horário inicial, com pesos totalizando 100%, critérios, metas por indicador, mínimo de votos, abstenção opcional, categoria e valor de reconhecimento. Regras, publicações, resultados e notas técnicas são congelados na abertura.
- Votação somente em ideias selecionadas, no período `[início, encerramento)`, com impedimento de voto próprio, conflitos, duplicação e critérios externos. Voto e notas são gravados na mesma transação. Cada funcionário pode participar novamente em ciclos futuros.
- Apuração após o prazo, com ranking agregado, proposta de bonificação, aprovação humana e divulgação separadas. Autoria e identidade dos votantes não são divulgadas.
- Dashboards, filtros, notificações privadas, CSV de indicadores validados e auditoria restrita. O CSV neutraliza fórmulas e separa unidades incompatíveis.
- Desativação de contas preserva registros corporativos e bloqueia novos acessos. Exclusão definitiva e alteração de histórico/auditoria são bloqueadas nas operações comuns de models.

## Metodologia e limites

Notas técnicas e de votação são normalizadas com `(nota − 1) / 4 × 100`. Os critérios técnicos usam pesos relativos; os critérios de votação devem somar 100%. Quando permitida, a abstenção redistribui os pesos entre os critérios respondidos de cada voto; um voto totalmente vazio não é aceito.

Resultados reais são calculados por indicador compatível em nome e unidade com as regras do ciclo: ganho observado dividido pela meta, limitado a 0–100. A dimensão corresponde à média dos indicadores compatíveis com dados. Para critérios qualitativos, a regra precisa definir a escala e a equipe registrar a nota validada no valor posterior. Sem um indicador compatível ou sem parecer técnico favorável, a ideia não pode entrar no ciclo. Dados ausentes não são substituídos por estimativas.

A nota final usa os três pesos configurados. Ideias abaixo do mínimo de votos não recebem nota final nem bonificação. Desempates seguem resultado real, avaliação técnica, votação e código da ideia em ordem alfabética. Até a quantidade máxima de ideias elegíveis gera propostas de reconhecimento; nenhum pagamento é executado pelo sistema.

Cinco ou mais votos com notas idênticas geram um alerta administrativo para revisão, sem excluir automaticamente votos nem afirmar fraude. A aprovação requer justificativa. Administradores com acesso à auditoria podem invalidar excepcionalmente um voto antes da apuração, preservando notas e registrando motivo; a unicidade permanece e impede um novo voto. Após a apuração, correções devem usar outro ciclo, preservando o anterior.

O reconhecimento admite autoria individual ou conjunta, divisão percentual e categorias com faixas sem sobreposição. O total da premiação é dividido em centavos, com o eventual centavo residual destinado ao último participante na ordem de ID; as parcelas sempre somam o valor da ideia. Anexos são opcionais e exclusivamente privados. Solicitações de privacidade permitem decisão auditada e anonimização dos dados da conta; textos, documentos e auditoria exigem revisão e retenção conforme política corporativa. Não há integração automática com pagamento. Não configure exclusão automática sem definir essa política.

## Uso

1. Para enviar uma ideia, use “Tenho uma ideia”: responda três passos, confira o resumo e confirme o envio. “Quero corrigir” mantém os campos preenchidos. A revisão também funciona sem JavaScript.
2. Para votar, abra “Votações”, escolha uma publicação e use “Conhecer os resultados e votar”. A tela mostra a versão revisada daquele ciclo, notas de 1 a 5 com descrições e uma conferência antes da confirmação. O recibo informa quando o voto já foi registrado.
3. Em “Seus avisos”, consulte notificações privadas, filtre as não lidas e marque-as como lidas.
4. Na Administração, cadastre critérios técnicos e atribua perfis e permissões. Novos cadastros sem perfil não podem consultar publicações corporativas.
5. O funcionário registra a proposta. A equipe usa a tela de detalhes para avançar etapas, avaliar, planejar, medir e validar.
6. Depois da conclusão, a equipe revisa os textos públicos e confirma a revisão de privacidade.
7. Em Votações, o administrador configura o ciclo, revisa a metodologia e seleciona publicações antes do início. Ciclos legados sem regras completas devem ser substituídos por novos ciclos.
8. Depois do encerramento, o administrador apura, revisa alertas, aprova e divulga reconhecimentos. Os funcionários consultam resultados agregados e seus próprios reconhecimentos no painel.

## Instalação local

Requisitos: PHP 8.3+, Composer, Node.js/npm e MySQL.

```sh
composer install
cp .env.example .env
```

Configure no `.env` `DB_CONNECTION=mysql` e o acesso ao banco MySQL (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD`) e então execute:

```sh
php artisan key:generate
php artisan migrate
php artisan db:seed
npm ci
npm run build
php artisan serve
```

O seeder cria dados de demonstração e usuários locais para desenvolvimento/testes. **Não use esses dados ou credenciais de demonstração em produção.**

Para desenvolver com atualização de assets, use `npm run dev` em outro terminal. Antes de iniciar, garanta que a chave da aplicação e as configurações locais estão definidas.

## Testes

```sh
php artisan test
```

A suíte funcional usa SQLite em memória, independentemente do banco de desenvolvimento; o teste de concorrência usa um arquivo temporário isolado. Os testes cobrem o fluxo completo, acessos, publicação revisada, pesos, cálculo, desempate, abstenção, rollback, bonificações e preservação do histórico. Um teste usa dois processos independentes e um banco SQLite temporário para verificar tentativas simultâneas de registrar o mesmo voto. Esse teste não substitui validação de concorrência em MySQL.

### Validação em MySQL

A suíte MySQL utiliza o banco exclusivo `ideias_corp_test`, separado do banco da aplicação. Configure as credenciais em `.env` e crie esse banco com o mesmo usuário, com permissão para criar e remover tabelas apenas nele. Não use dados reais no banco de testes.

```sh
php artisan config:clear
vendor/bin/phpunit --configuration phpunit.mysql.xml
```

O bootstrap exige um nome terminado em `_test`, rejeita o banco da aplicação e bloqueia configuração em cache. A suíte recria as tabelas do banco de testes. O teste de concorrência usa dois processos e bloqueios de linha reais quando executado com esse perfil. O perfil padrão continua usando SQLite.

## Administração de acesso (branch adm)

Na Administração, use **Pessoas e acessos** para cadastrar e editar pessoas, definir setor, equipes, perfil e permissões adicionais. Há filtros por nome/e-mail, contas bloqueadas ou desativadas, e-mails pendentes e pessoas sem perfil. A senha inicial só é definida no cadastro; alterações de perfil não substituem a senha. E-mails já utilizados por contas desativadas continuam reservados para permitir reativação sem perder o histórico.

O bloqueio exige justificativa, revoga sessões e acesso lembrado e preserva ideias, votos e avaliações. A reativação também exige justificativa e obriga novo login. Contas desativadas pelo titular podem ser reativadas pelo administrador, preservando associações. O último administrador ativo com e-mail confirmado não pode perder o acesso, inclusive por mudança de e-mail ou desativação do próprio perfil.

Em **Setores e equipes**, cadastre, edite e desative os agrupamentos. Cadastros inativos permanecem no histórico e não podem receber novas associações; associações anteriores podem ser mantidas ou removidas explicitamente. Equipes são agrupamentos organizacionais e não concedem permissões. Os perfis e permissões continuam sendo a fonte de autorização.

A confirmação de e-mail é obrigatória antes de acessar as áreas do sistema. Novos cadastros e alterações de e-mail enviam um link assinado; o titular pode corrigir o endereço e reenviar a mensagem sem já estar verificado. A confirmação do endereço não concede um perfil automaticamente: o acesso corporativo ainda precisa de liberação administrativa. Administradores não confirmam o e-mail em nome de outra pessoa.

Após atualizar esta branch, execute `php artisan migrate` para adicionar equipes, associações, situação dos setores e controle de bloqueio/sessões. O banco existente é preservado. No ambiente local, `MAIL_MAILER=log` registra as mensagens em `storage/logs/laravel.log`; para entrega real, configure `MAIL_MAILER=smtp`, servidor, porta, usuário, senha e remetente no `.env`, e execute `php artisan config:clear`. Links de confirmação expiram conforme a configuração de autenticação e não devem ser compartilhados.

<<<<<<< HEAD
## Administração e primeiro acesso

Na administração, cadastre a pessoa com e-mail, setor, equipes e perfil de acesso. O sistema gera uma senha aleatória desconhecida e envia um convite: o titular define a própria senha pelo link, confirmando também seu e-mail. O link usa o token de recuperação, expira conforme `auth.passwords.users.expire` (60 minutos por padrão) e só pode ser usado uma vez. Convites pendentes podem ser reenviados na gestão da pessoa ou por “Esqueci minha senha”; contas bloqueadas não concluem o convite. O cadastro público existente continua criando contas sem perfil, que dependem de liberação administrativa.

Contas desativadas podem ter seus acessos revisados antes da reativação. Trocar o e-mail invalida as sessões e o acesso persistente anteriores; uma nova confirmação é necessária. A recuperação de senha também revoga sessões anteriores. Na edição do próprio perfil, a sessão atual é preservada para permitir a confirmação do novo endereço.

Antes de usar os convites, execute as migrations, configure o transporte de e-mail e teste a entrega em uma caixa real. Os testes automatizados usam notificações simuladas.
=======

## Conclusão do fluxo e reconhecimento

- O parecer mais recente deve ser favorável para aprovar uma proposta e para congelar sua nota técnica no ciclo.
- Administração permite editar e desativar critérios. Cada parecer conserva as notas, descrições e pesos de sua época.
- Rascunhos podem ter nome e período corrigidos ou ser cancelados. Para trocar sua metodologia, cancele e crie outro ciclo. Ciclos já abertos continuam imutáveis.
- As categorias opcionais possuem nome, faixa inclusiva de pontuação, valor por ideia e limite de premiados. Faixas não podem se sobrepor; sem categorias opcionais, vale o reconhecimento principal. As dimensões de desempate podem ser ordenadas antes da abertura; o código da ideia resolve o empate restante.
- Participantes e percentuais são definidos por administrador com permissão de autoria, durante envio ou triagem. Inclua o autor principal e distribua exatamente 100%. Todos os participantes recebem acesso privado, notificações e impedimento de voto/avaliação. A divisão é congelada com o ciclo.
- Alertas de notas idênticas têm limite configurável. Exigem revisão por administrador sem conflito e com acesso à auditoria antes da aprovação do prêmio. Uma apuração comprometida deve gerar recusa justificada do prêmio e novo ciclo; resultados apurados não são reescritos.
- Reconhecimentos propostos podem ser aprovados ou recusados com justificativa. Após divulgação, não podem ser recusados. Ideias sem prêmio também podem ser finalizadas na tela do ciclo encerrado.
- Relatórios incluem participação, evolução mensal, tempo por etapa e variações agrupadas por indicador, unidade e período iguais. O filtro de datas considera o envio da proposta. Variação é depois menos antes; números negativos indicam redução. Confira a independência das medições antes de interpretar somas como economia total.

## Documentos e privacidade

Configure `IDEA_ATTACHMENTS_ENABLED=true` para permitir até dez documentos por proposta, com até 10 MB cada, nos formatos PDF, PNG, JPG e TXT. Configure também PHP com `upload_max_filesize=10M` e `post_max_size=12M`. Arquivos ficam em `storage/app/private`, recebem nomes aleatórios e são baixados com nome genérico por rota autenticada e autorizada. Nomes originais e conteúdo não entram na publicação, votação ou CSV. Os arquivos não são expostos publicamente; não há promessa de remoção automática de metadados ou análise antivírus. Antes de enviar, remova dados pessoais desnecessários. Não crie um link público para o diretório privado.

Em Meu perfil, cada pessoa pode baixar seus dados e solicitar análise de privacidade após reconfirmar a senha. A administração com acesso à auditoria analisa o pedido em Administração → Solicitações de privacidade. A anonimização da conta exige concluir propostas/reconhecimentos pendentes e revisar previamente textos, documentos e registros de negócio. Ela substitui nome/e-mail, remove acessos e invalida sessões; não remove automaticamente documentos e menções pessoais em registros históricos. A justificativa deve documentar dados preservados, finalidade, prazo e comunicação ao solicitante. Este procedimento depende da política aprovada pela empresa.

`PRIVACY_CONTACT` informa o canal de atendimento. `READ_NOTIFICATIONS_RETENTION_DAYS=0` mantém a limpeza automática desativada. Defina um prazo aprovado para remover somente notificações já lidas, via `php artisan app:prune-read-notifications`. Propostas, votos, reconhecimentos e auditoria não são apagados por essa rotina. Revise também a retenção de logs, backups e tokens conforme a política operacional.

## Preparação de produção

A configuração de referência está em `.env.production.example`; o servidor Nginx de referência está em `nginx.production.conf`. Esses arquivos não contêm credenciais e precisam ser adaptados à hospedagem, domínio e serviço de e-mail. As otimizações seguem a [documentação de implantação do Laravel](https://laravel.com/docs/13.x/deployment).

Use um banco e usuário exclusivos no servidor de produção. O MySQL local do projeto utiliza a porta 3307; não reutilize ou modifique os bancos do trabalho. Não copie a senha, a chave ou as contas de demonstração locais para produção.

Antes de enviar ao servidor, execute a suíte em uma máquina de desenvolvimento e gere um pacote com os arquivos públicos compilados:

```bash
php artisan test --compact
vendor/bin/phpunit --configuration phpunit.mysql.xml
node --test tests/idea-wizard.test.mjs
./package-release.sh
```

O pacote fica em `storage/app/releases/`, com checksum SHA-256, sem `.env` local, credenciais, documentos, backups, `node_modules`, dependências de desenvolvimento PHP ou cache de configuração. Envie o pacote para a hospedagem e extraia no diretório da aplicação; instale as dependências PHP com o lockfile. Crie `storage/app/private`, `storage/framework/cache/data`, `storage/framework/sessions`, `storage/framework/views` e `storage/logs`, atribuindo os diretórios graváveis ao usuário do PHP. O servidor de produção não precisa de Node, npm, Tailwind ou servidor Vite.

Primeira instalação, no diretório extraído no servidor:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
cp .env.production.example .env
```

O fuso horário pode ser configurado com `APP_TIMEZONE`. Se importar dados de uma instalação antiga que usava UTC, preserve UTC ou planeje uma conversão dos horários antes de mudar esse valor.

Preencha o `.env` diretamente no servidor: domínio HTTPS real, MySQL dedicado, credenciais SMTP, remetente autorizado e contato de privacidade. Preserve o arquivo fora do Git, com acesso restrito. O modelo usa SMTP com TLS na porta 465; adapte `MAIL_SCHEME` e a porta conforme o provedor. Confirme SPF/DKIM e remetente no serviço contratado. `REGISTRATION_ENABLED=false` mantém o cadastro de pessoas na administração; links públicos de cadastro ficam ocultos.

```bash
php artisan key:generate --no-interaction
php artisan migrate --force
php artisan db:seed --class=AccessControlSeeder --force
php artisan optimize
php artisan app:create-administrator administrador@sua-empresa.com --name="Administrador"
```

A senha é solicitada de forma oculta, sem argumento na linha de comando. A conta precisa confirmar o endereço pelo e-mail recebido. Nunca execute o seeder de demonstração em produção: ele é bloqueado nesse ambiente. O seeder de acesso cria apenas perfis e permissões, sem contas ou propostas. Após confirmar o e-mail do primeiro administrador, crie os setores, equipes, critérios e pessoas pela interface.

Configure Nginx para servir **somente `public/`**, certificado TLS válido, PHP-FPM compatível com `composer check-platform-reqs --no-dev`, e permissões de escrita apenas em `storage/` e `bootstrap/cache/` para o processo PHP. Ajuste domínio, certificados, diretório e socket no modelo Nginx. O exemplo bloqueia execução de outros arquivos PHP e acesso a diretórios internos. Se houver proxy ou balanceador, configure confiança somente nos endereços reais dessa infraestrutura antes de validar cookies e links assinados. Não use `php artisan serve` para publicar o serviço.

O processamento padrão usa `QUEUE_CONNECTION=sync` e não exige worker. Caso mude para fila assíncrona, configure e monitore o worker antes de publicar. Configure o scheduler, usando o usuário do PHP e o caminho real da aplicação:

```cron
* * * * * cd /var/www/ideias && php artisan schedule:run >> /dev/null 2>&1
```

Execute a verificação no ambiente de destino:

```bash
php artisan app:production-check
```

Ela retorna erro enquanto houver configuração insegura, migrations pendentes, contas demonstrativas ou falta de administrador ativo/verificado. Não valida DNS/TLS externo, entrega SMTP, disponibilidade, restauração externa ou navegação. Teste login, confirmação de e-mail, recuperação de senha, criação de ideia, avaliação, resultados, publicação, voto e reconhecimento com contas de cada perfil. Valide computador/celular, teclado e leitores de tela. Monitore `/up`, erros HTTP, espaço em disco, logs e falhas do scheduler, sem publicar dados de acesso em logs externos.

### Atualizações e backups

Em produção, faça backup antes de cada atualização, com gravações suspensas. Use manutenção e aguarde requisições em andamento; suspenda temporariamente outras tarefas que escrevam no banco durante a cópia para que as contagens sejam comparáveis:

```bash
php artisan down --retry=60
php artisan app:backup
```

O comando precisa dos clientes `mysqldump` e `tar`, gera dump transacional MySQL, arquivo dos documentos privados e manifesto com checksums/contagens em `storage/app/backups/`. Cópias têm permissões restritas, mas **não são criptografadas pelo comando**: transfira para armazenamento externo cifrado, com controle de acesso e retenção aprovados. Guarde a `APP_KEY` em cofre separado. Logs e `.env` não são incluídos. Mantenha o código da versão correspondente junto ao inventário do backup.

A conferência exige um banco **já criado, vazio, exclusivo e terminado em `_test`**, diferente do banco da aplicação, com acesso para o mesmo usuário MySQL:

```bash
php artisan app:check-backup-restore storage/app/backups/PASTA_DA_COPIA --database=ideias_restore_test
```

O comando confere checksums, importa somente nesse banco vazio, compara contagens de todas as tabelas e verifica o arquivo de documentos. Não apaga tabelas existentes e não restaura sobre o banco principal. O banco restaurado fica disponível para inspeção e deve ser removido pelo operador quando terminar. Faça também um ensaio completo em hospedagem isolada: mesma versão do código, chave preservada e extração de `documents.tar.gz` para um diretório vazio fora da raiz pública. Verifique os downloads e fluxos da aplicação nessa cópia antes de considerar a recuperação operacional comprovada.

Depois do backup e da conferência, publique o código/artefatos da versão e execute:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize
php artisan app:production-check
php artisan up
```

Se algum passo falhar, mantenha a manutenção e investigue. Não use `migrate:fresh`, `db:wipe` ou rollback destrutivo como procedimento de atualização. Reverter o código só é seguro se a versão anterior for compatível com o esquema; restauração de dados deve seguir ensaio em ambiente separado.

No desenvolvimento, a suíte MySQL continua usando exclusivamente `ideias_corp_test`, protegida pelo bootstrap de testes. Nunca rode testes automatizados apontando para o banco publicado.


### Auditoria das ferramentas de compilação

Na verificação desta entrega, `composer audit` e `npm audit --omit=dev` não apontaram vulnerabilidades. A auditoria completa npm apontou sete alertas em dependências de desenvolvimento ligadas ao Tailwind 3: cinco altos e dois moderados. Há dois avisos de origem, propagados pela árvore: [recursão em braces](https://github.com/advisories/GHSA-vfj7-8cjw-p6xm), sem versão corrigida informada no aviso, e [complexidade de análise em postcss-selector-parser](https://github.com/advisories/GHSA-rj75-hqrm-r3gf). Não se trata de sete falhas independentes no código da aplicação.

A geração do pacote usa somente código/configuração confiáveis do repositório. Não compile CSS, templates ou padrões enviados pelos usuários e não exponha o compilador como serviço. O pacote publicado contém CSS/JS estáticos, sem essas ferramentas. A correção automática sugerida pelo npm envolve Tailwind 4 e exige migração e revisão da interface; não execute `npm audit fix --force` sem essa revisão. Reavalie os avisos em cada atualização de dependências.


Validação local desta entrega: 139 testes PHP em SQLite e MySQL, três testes JavaScript do formulário por etapas, compilação de produção e instalação do pacote sem dependências de desenvolvimento. A navegação foi conferida em Chrome headless com banco SQLite temporário: cadastro por etapas/revisão/envio e voto/revisão/confirmação em viewport móvel, além de administração, privacidade e relatórios no desktop, sem erros JavaScript nesses percursos. Isso não substitui a validação de SMTP, TLS e acessibilidade completa na hospedagem de destino.
>>>>>>> 787622d (admfinaliza)
