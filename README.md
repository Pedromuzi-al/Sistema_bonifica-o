# Plataforma Corporativa de Ideias

> Estado atual (22/10/2026): prazo transferido para novembro; migração autorizada para Tailwind 4.3.3 concluída, com auditoria npm sem vulnerabilidades. No modo manual, o administrador define/redefine a senha em Pessoas e acessos, inclusive para o primeiro acesso, com dois campos e Concluir. Trocar o próprio e-mail exige a senha atual; trocar a própria senha revoga acessos antigos. Consulte a atualização mais recente de `STATUS.md` para validações e pendências reais antes da publicação.


Aplicação Laravel para registrar propostas de melhoria, priorizar ideias antes da implementação, acompanhar a execução e reconhecer contribuições e resultados. A autoria é privada para os demais funcionários; usuários autorizados da equipe de avaliação e administração podem consultá-la.

## Regras financeiras e criação de ciclos

No menu **Regras financeiras**, cadastre modelos de critérios técnicos (perguntas, orientações e pesos que somam 100%) e regras de bonificação (valor por ideia, nota mínima, abstenção e limite de votos idênticos para revisão). Ambos podem ser editados ou inativados; o acesso é administrativo. Os critérios individuais dos pareceres anteriores continuam acessíveis na área de critérios, em seção própria.

Em **Votações → Novo ciclo**, escolha o modelo e a regra nos dois seletores. As prévias mostram o que será aplicado. Somente cadastros ativos são aceitos, e o servidor usa os valores cadastrados. Uma regra com valor zero permite seleção sem dinheiro. O mínimo de votos e o limite de ideias continuam no ciclo. A configuração é copiada para o rascunho e preservada na abertura, de modo que futuras edições não mudam os ciclos existentes. Reconhecimento por resultados comprovados mantém seu formulário próprio.

As migrations de 22/10 acrescentam `technical_models` e `bonus_policies`, separadas dos registros históricos de bonificação. Atualize o esquema com `php artisan migrate` após o backup; não use seed de demonstração para preencher o banco operacional. Na base local, as duas migrations já foram aplicadas nesta entrega.

## Arquitetura

- **Backend:** PHP 8.3 ou superior, Laravel 13 e Eloquent.
- **Banco de dados:** MySQL em execução normal; SQLite em memória nos testes.
- **Autorização:** Policies do Laravel e papéis/permissões de `spatie/laravel-permission`.
- **Interface:** Blade, Tailwind CSS, Alpine.js e Vite.
- **Testes:** PHPUnit por meio de `php artisan test`.

O código está organizado pelas convenções do Laravel: models em `app/Models`, controllers e requests HTTP em `app/Http`, autorização em `app/Policies`, regras reutilizáveis em `app/Services`, migrations em `database/migrations` e telas Blade em `resources/views`.

## Fluxo principal

1. O funcionário registra título, problema, sugestão e setores. Estimativas de benefícios e recursos são opcionais.
2. Em Revisão de ideias, a equipe confere o texto já preenchido e escolhe Liberar para votação, Pedir ajuste ou Arquivar. A liberação publica uma versão revisada sem exigir mudança manual para triagem/avaliação nem notas técnicas.
3. Um ciclo de seleção coleta votos sobre utilidade, relevância e benefício esperado, mesmo antes da implementação.
4. A apuração classifica as propostas. Um administrador sem conflito decide, com justificativa, quais avançam e quais permanecem no banco para outros ciclos.
5. As selecionadas seguem para plano, responsável, prazo e implementação. A votação não muda a etapa de execução.
6. A bonificação pela ideia selecionada é opcional e exige aprovação separada. Depois da execução, medições e evidências podem fundamentar um reconhecimento por resultados.
7. Ciclos anteriores mantêm suas regras de reconhecimento por resultados; não são convertidos.

Veja o passo a passo completo em [docs/CICLO_SELECAO.md](docs/CICLO_SELECAO.md).

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
- Seleção antes da execução: envio → triagem/publicação revisada → votação → decisão → aprovação ou banco de ideias. Na execução: plano → implementação → validação → conclusão. O ciclo posterior de resultados permite reconhecimento e finalização. O percurso técnico anterior continua disponível. Solicitações de informações e rejeições exigem justificativa; o autor pode responder privadamente.
- Pareceres técnicos com critérios configuráveis, notas de 1 a 5 e histórico de avaliações. Aprovação pelo percurso técnico exige parecer favorável; a seleção em ciclo exige decisão justificada da equipe. Execução exige plano, responsável e prazo.
- Indicadores flexíveis com valores anterior/posterior, unidade, período, evidência, descrição qualitativa e limitações. Diferenças e percentuais são calculados quando há dados; edição invalida a validação anterior.
- Publicação revisada: funcionários recebem somente textos públicos aprovados, sem o conteúdo interno da proposta. A permissão `view_author_identity` é independente da permissão de avaliar.
- Ciclos de reconhecimento por resultados configurados e abertos antes do horário inicial, com pesos totalizando 100%, critérios, metas por indicador, mínimo de votos, abstenção opcional, categoria e valor de reconhecimento. Regras, publicações, resultados e notas técnicas são congelados na abertura.
- Votação somente em ideias selecionadas, no período `[início, encerramento)`, com impedimento de voto próprio, conflitos, duplicação e critérios externos. Voto e notas são gravados na mesma transação. Cada funcionário pode participar novamente em ciclos futuros.
- Apuração após o prazo, com ranking agregado, proposta de bonificação, aprovação humana e divulgação separadas. Autoria e identidade dos votantes não são divulgadas.
- Dashboards, filtros, notificações privadas, CSV de indicadores validados e auditoria restrita. O CSV neutraliza fórmulas e separa unidades incompatíveis.
- Desativação de contas preserva registros corporativos e bloqueia novos acessos. Exclusão definitiva e alteração de histórico/auditoria são bloqueadas nas operações comuns de models.

## Metodologia e limites

Notas técnicas e de votação são normalizadas com `(nota − 1) / 4 × 100`. Os critérios técnicos usam pesos relativos; os critérios de votação devem somar 100%. Quando permitida, a abstenção redistribui os pesos entre os critérios respondidos de cada voto; um voto totalmente vazio não é aceito.

Resultados reais são calculados por indicador compatível em nome e unidade com as regras do ciclo: ganho observado dividido pela meta, limitado a 0–100. A dimensão corresponde à média dos indicadores compatíveis com dados. Para critérios qualitativos, a regra precisa definir a escala e a equipe registrar a nota validada no valor posterior. No ciclo de reconhecimento por resultados, sem indicador compatível ou parecer técnico favorável a ideia não pode entrar. O ciclo de seleção anterior à execução usa somente a publicação revisada e a situação da proposta. Dados ausentes não são substituídos por estimativas.

No reconhecimento por resultados, a nota final usa os três pesos configurados. Ideias abaixo do mínimo de votos não recebem nota final nem bonificação. Desempates seguem resultado real, avaliação técnica, votação e código da ideia em ordem alfabética. Até a quantidade máxima de ideias elegíveis gera propostas de reconhecimento; nenhum pagamento é executado pelo sistema.

Cinco ou mais votos com notas idênticas geram um alerta administrativo para revisão, sem excluir automaticamente votos nem afirmar fraude. A aprovação requer justificativa. Administradores com acesso à auditoria podem invalidar excepcionalmente um voto antes da apuração, preservando notas e registrando motivo; a unicidade permanece e impede um novo voto. Após a apuração, correções devem usar outro ciclo, preservando o anterior.

O reconhecimento admite autoria individual ou conjunta, divisão percentual e categorias com faixas sem sobreposição. O total da premiação é dividido em centavos, com o eventual centavo residual destinado ao último participante na ordem de ID; as parcelas sempre somam o valor da ideia. Anexos são opcionais e exclusivamente privados. Solicitações de privacidade permitem decisão auditada e anonimização dos dados da conta; textos, documentos e auditoria exigem revisão e retenção conforme política corporativa. Não há integração automática com pagamento. Não configure exclusão automática sem definir essa política.

## Uso

1. Para enviar uma ideia, use “Tenho uma ideia”: responda três passos, confira o resumo e confirme o envio. “Quero corrigir” mantém os campos preenchidos. A revisão também funciona sem JavaScript.
2. Para votar, abra “Votações”, escolha uma publicação e use “Conhecer a ideia e votar”. A tela mostra a versão revisada daquele ciclo, notas de 1 a 5 com descrições e uma conferência antes da confirmação. O recibo informa quando o voto já foi registrado.
3. Em “Seus avisos”, consulte notificações privadas, filtre as não lidas e marque-as como lidas.
4. Na Administração, cadastre critérios técnicos e atribua perfis e permissões. Novos cadastros sem perfil não podem consultar publicações corporativas.
5. A equipe faz a triagem e usa Revisar proposta para votação, sem exigir implementação ou indicadores.
6. Em Votações → Novo ciclo, o administrador cria um ciclo de seleção, escolhe as propostas e confirma antes do início. Após o prazo, apura e registra a decisão de avançar ou manter no banco.
7. Na proposta selecionada, defina plano, responsável e prazo; acompanhe execução, medição e validação. O parecer técnico posterior à seleção pode ser registrado antes do ciclo de resultados.
8. Para reconhecer benefícios comprovados, publique os resultados revisados e use Criar reconhecimento por resultados comprovados. Aprovação e divulgação são ações separadas; não existe pagamento automático.

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

Na Administração, use **Pessoas e acessos** para cadastrar e editar pessoas, definir setor, equipes, perfil e permissões adicionais. Há filtros por nome/e-mail, contas bloqueadas ou desativadas, primeiro acesso pendente e pessoas sem perfil. O filtro de e-mail pendente aparece quando a verificação está exigida. No modo manual, o administrador define a primeira senha pelo botão Redefinir senha; no modo por e-mail, o titular recebe convite. Salvar dados/permissões não substitui a senha. E-mails já utilizados por contas desativadas continuam reservados para permitir reativação sem perder o histórico.

O bloqueio exige justificativa, revoga sessões e acesso lembrado e preserva ideias, votos e avaliações. A reativação também exige justificativa e obriga novo login. Contas desativadas pelo titular podem ser reativadas pelo administrador, preservando associações. O último administrador ativo e com primeiro acesso concluído não pode perder o acesso. Quando a verificação é exigida, também precisa ter e-mail confirmado.

Em **Setores e equipes**, cadastre, edite e desative os agrupamentos. Cadastros inativos permanecem no histórico e não podem receber novas associações; associações anteriores podem ser mantidas ou removidas explicitamente. Equipes são agrupamentos organizacionais e não concedem permissões. Os perfis e permissões continuam sendo a fonte de autorização.

Nesta primeira rodada, `EMAIL_VERIFICATION_REQUIRED=false` dispensa a confirmação de e-mail. O endereço continua sendo o identificador de login; não é marcado artificialmente como confirmado. Perfis, permissões e bloqueios continuam obrigatórios. Para exigir confirmação futuramente, ative a opção e valide a entrega das notificações por e-mail.

Após atualizar esta branch, execute `php artisan migrate` para adicionar equipes, associações, situação dos setores e controle de bloqueio/sessões. O banco existente é preservado. No ambiente local, `MAIL_MAILER=log` registra as mensagens em `storage/logs/laravel.log`; para entrega real, configure `MAIL_MAILER=smtp`, servidor, porta, usuário, senha e remetente no `.env`, e execute `php artisan config:clear`. Links de confirmação expiram conforme a configuração de autenticação e não devem ser compartilhados.

## Administração e primeiro acesso

Na administração, cadastre a pessoa com e-mail, setor, equipes e perfil de acesso. A senha inicial é aleatória e a conta fica pendente. No modo manual, abra Gerenciar acesso → Redefinir senha, preencha Nova senha/Confirmar senha e clique em Concluir. Isso libera também o primeiro acesso. Entregue a senha somente ao titular, por canal privado. Cancelar limpa os campos. A pessoa pode alterar sua senha depois em Meu perfil.

A operação revoga sessões, acesso persistente e tokens anteriores, sem alterar permissões ou marcar o e-mail como confirmado. A auditoria registra responsável e conta afetada, sem senha ou hash. Contas bloqueadas/desativadas precisam ser reativadas antes. Em Meu perfil, alterar o próprio e-mail exige a senha atual; mudar somente o nome não exige. Trocar a própria senha encerra os acessos antigos e renova a sessão atual.

Com `ACCOUNT_INVITATION_DELIVERY=email`, o convite continua sendo enviado ao endereço e sua utilização confirma o e-mail. Valide SMTP antes de habilitar. A recuperação por e-mail não revela publicamente se o endereço existe. O autocadastro opcional atribui somente o perfil Funcionário e exige que `AccessControlSeeder` tenha preparado os perfis; o modelo de produção mantém o cadastro público desligado.

O guia operacional está em [docs/ENTREGA_VOTACAO_SEXTA.md](docs/ENTREGA_VOTACAO_SEXTA.md), cujo nome é histórico. O prazo atual é novembro de 2026. Acompanhe pendências em [STATUS.md](STATUS.md).

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

Preencha o `.env` diretamente no servidor: domínio HTTPS real, MySQL dedicado e contato de privacidade. SMTP e remetente autorizado são necessários quando habilitar confirmação ou convites por e-mail. Preserve o arquivo fora do Git, com acesso restrito. O modelo usa SMTP com TLS na porta 465; adapte `MAIL_SCHEME` e a porta conforme o provedor. Confirme SPF/DKIM e remetente no serviço contratado. `REGISTRATION_ENABLED=false` mantém o cadastro de pessoas na administração; links públicos de cadastro ficam ocultos.

```bash
php artisan key:generate --no-interaction
php artisan migrate --force
php artisan db:seed --class=AccessControlSeeder --force
php artisan optimize
php artisan app:create-administrator administrador@sua-empresa.com --name="Administrador"
```

A senha é solicitada de forma oculta, sem argumento na linha de comando. Com a confirmação desativada, a conta acessa usando a senha definida no comando. Quando exigida, também precisa confirmar o endereço pelo e-mail recebido. Nunca execute o seeder de demonstração em produção: ele é bloqueado nesse ambiente. O seeder de acesso cria apenas perfis e permissões, sem contas ou propostas. Após entrar com o primeiro administrador, crie os setores, equipes, critérios e pessoas pela interface.

Configure Nginx para servir **somente `public/`**, certificado TLS válido, PHP-FPM compatível com `composer check-platform-reqs --no-dev`, e permissões de escrita apenas em `storage/` e `bootstrap/cache/` para o processo PHP. Ajuste domínio, certificados, diretório e socket no modelo Nginx. O exemplo bloqueia execução de outros arquivos PHP e acesso a diretórios internos. Se houver proxy ou balanceador, configure confiança somente nos endereços reais dessa infraestrutura antes de validar cookies e links assinados. Não use `php artisan serve` para publicar o serviço.

O processamento padrão usa `QUEUE_CONNECTION=sync` e não exige worker. Caso mude para fila assíncrona, configure e monitore o worker antes de publicar. Configure o scheduler, usando o usuário do PHP e o caminho real da aplicação:

```cron
* * * * * cd /var/www/ideias && php artisan schedule:run >> /dev/null 2>&1
```

Execute a verificação no ambiente de destino:

```bash
php artisan app:production-check
```

Ela retorna erro enquanto houver configuração insegura, migrations pendentes, contas demonstrativas ou falta de administrador liberado (verificado quando exigido). Não valida DNS/TLS externo, entrega SMTP, disponibilidade, restauração externa ou navegação. Teste login, primeiro acesso/recuperação direta pelo administrador (ou convite por e-mail, quando configurado), confirmação de e-mail quando habilitada, criação de ideia, avaliação, resultados, publicação, voto e reconhecimento com contas de cada perfil. Valide computador/celular, teclado e leitores de tela. Monitore `/up`, erros HTTP, espaço em disco, logs e falhas do scheduler, sem publicar dados de acesso em logs externos.

### Atualizações e backups

Em produção, faça backup antes de cada atualização, com gravações suspensas. Use manutenção e aguarde requisições em andamento; suspenda temporariamente outras tarefas que escrevam no banco durante a cópia para que as contagens sejam comparáveis:

```bash
php artisan down --retry=60
php artisan app:backup
```

Em produção MySQL, o comando precisa dos clientes `mysqldump` e `tar`, gera dump transacional MySQL, arquivo dos documentos privados e manifesto com checksums/contagens em `storage/app/backups/`. Cópias têm permissões restritas, mas **não são criptografadas pelo comando**: transfira para armazenamento externo cifrado, com controle de acesso e retenção aprovados. Guarde a `APP_KEY` em cofre separado. Logs e `.env` não são incluídos. Mantenha o código da versão correspondente junto ao inventário do backup.

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


### Tailwind 4 e auditoria das ferramentas de compilação

Migração autorizada pelo usuário e concluída em 22/10/2026: Tailwind **4.3.3**, plugin Vite **4.3.3** e plugin de formulários **0.5.11**. Use `npm ci --ignore-scripts` e `npm run build`; para desenvolvimento, `npm run dev`. A configuração está em `resources/css/app.css` e `vite.config.js`, com fontes explícitas para os templates, JavaScript, classes da aplicação e paginação do Laravel. Não recrie os arquivos antigos `tailwind.config.js`/`postcss.config.js` nem siga instruções antigas de inicialização da versão 3.

Os utilitários próprios `stack-1`, `stack-2`, `stack-3`, `stack-4`, `stack-5`, `stack-6` e `stack-8` preservam as pilhas verticais anteriores; telas novas devem preferir flex/grid com `gap`. A variante escura é controlada pela classe `dark` no elemento raiz. A paleta e as bordas/focos da plataforma foram mantidos; o teste de navegador inclui os dois temas e verificações básicas de contraste para títulos em superfícies lisas.

A auditoria npm completa e a auditoria sem dependências de desenvolvimento retornaram **zero vulnerabilidades** após a migração e instalação pelo lockfile. Os sete alertas da cadeia anterior do Tailwind 3 foram removidos da árvore, sem ignorar avisos ou forçar uma atualização automática. A auditoria PHP anterior também não reportou advisories; avisos futuros devem ser avaliados novamente.

A versão 4 exige navegadores modernos: Safari 16.4+, Chrome 111+ ou Firefox 128+, conforme o [guia oficial de migração](https://tailwindcss.com/docs/upgrade-guide). A validação automatizada desta entrega usa Chrome; não certifica todas as versões desses navegadores. Referência de integração: [Tailwind com Vite](https://tailwindcss.com/docs/installation/using-vite).

## QA e revisão final local

Consulte [a entrega e as alterações](docs/ENTREGA_LOCAL.md), [o roteiro por funcionalidade](docs/ROTEIRO_QA.md) e [a validação realizada](docs/VALIDACAO_LOCAL.md).

`npm run test:browser` recompila os assets e executa a conferência em Chrome com banco e arquivos temporários. O comando não usa a base da aplicação; exige Google Chrome instalado ou `QA_CHROME_EXECUTABLE`. Evidências ficam em `storage/app/qa-reports/`.

A migration `2026_10_18_120000_add_private_document_review` adiciona revisão, restrição de acesso, preservação obrigatória e prazo individual aos documentos privados. Na proposta, um administrador com auditoria pode confirmar a senha, registrar justificativa e revisar/excluir o arquivo. Aprovar não publica o documento. Exclusão mantém o registro/auditoria, mas remove o arquivo armazenado. A rotina `app:purge-private-documents` apenas simula; `--execute` aplica decisões vencidas e repete exclusões pendentes, sempre respeitando preservação obrigatória. O scheduler executa essa rotina diariamente. Nenhum prazo é criado automaticamente.

No ambiente local SQLite, `app:backup` também cria cópia do banco e documentos. `app:check-backup-restore CAMINHO --database=conferencia_test` restaura em uma pasta nova dentro de `storage/app/restore-check`, confere integridade do banco, contagens e hashes dos documentos. Não substitui a base da aplicação. As instruções MySQL de produção continuam na seção de operação.

Categorias opcionais aceitam mínimos próprios de votos, nota técnica e resultados, além de uma categoria exata da publicação. Requisitos são cumulativos e ficam congelados na abertura. Nos relatórios, benefícios são separados também pelo setor de origem; o CSV inclui setor e período de medição.

### Setores da empresa e horários de ciclos

O catálogo ativo é Material Didático, Sucesso do Cliente, Tecnologia, Produto e Administração. A migration `2026_10_18_180000_update_company_sectors` aplica as correspondências aprovadas: Operações → Produto, Financeiro → Administração, Vendas → Sucesso do Cliente; Tecnologia continua. Pessoas, equipes, origem e setores beneficiados acompanham a troca. Recursos Humanos fica inativo para preservar registros já vinculados. Publicações e snapshots antigos não são reescritos. Por haver consolidação de vínculos, o rollback dessa migration não tenta inventar o setor original de cada registro; para recuperação integral utilize o backup anterior.

Ciclos podem começar e terminar no mesmo dia. O formulário usa `CYCLE_TIMEZONE=America/Sao_Paulo` por padrão, converte os horários para o fuso interno na validação e os exibe novamente no fuso do programa. O início deve continuar no futuro para permitir selecionar as ideias e abrir o rascunho; o fim deve ser posterior ao início. Nenhuma regra exige aguardar até amanhã. Os horários já gravados de ciclos existentes permanecem intactos.


## Continuar no PC do trabalho

Consulte [o guia de preparação do outro computador](docs/CONTINUAR_EM_OUTRO_PC.md) e [o texto para retomar no Codex](docs/RETOMAR_NO_CODEX.md). O Git transfere código e documentação; banco, anexos privados e `.env` exigem preparação separada.


## Relatórios e resultados por ciclo

Relatórios abre a rodada mais recente e permite escolher outro ciclo. Após a apuração, mostra classificação, decisão e bonificação em colunas distintas, com indicadores de participação, barras de pontuação e CSV por ciclo. A mesma classificação aparece no detalhe do ciclo; as propostas completas ficam em detalhes expansíveis. Ranking não é exibido durante votação, e funcionários só veem bonificações divulgadas. Os indicadores históricos e seu CSV permanecem na área complementar Indicadores de execução. Consulte [o guia](docs/RELATORIO_POR_CICLO.md).
