# Continuar o projeto em outro computador

## O que levar

O repositório Git leva código, migrations, testes, `STATUS.md` e os guias de `docs/`. A branch de trabalho é `adm`. O ponto de partida desta preparação foi o commit `e39657f` (lógica nova e visual novo).

Git não leva automaticamente o chat, `.env`, banco local, documentos privados, dependências (`vendor`, `node_modules`) nem assets compilados. A conta usada no Codex não substitui a transferência desses arquivos. Não coloque senhas, `.env`, chaves ou backups no Git.

## Obter o código no PC do trabalho

Se ainda não houver uma cópia, use a URL do repositório que você já utiliza, sem incluir token ou senha no comando:

```bash
git clone --branch adm URL_DO_REPOSITORIO
```

Entre na pasta que contém `artisan` e `composer.json`. Se a cópia já existir, confira primeiro se há alterações locais:

```bash
git status
git switch adm
git pull --ff-only origin adm
```

Se houver alterações locais ou branches divergentes, preserve-as antes de atualizar. Não use `reset --hard` para resolver isso.

## Preparar uma base local nova para QA

Os comandos abaixo são para uma cópia nova, sem `.env` e sem banco existente. Use PHP 8.3 ou superior com as extensões exigidas pelo Composer e PDO SQLite, Composer e Node compatível com Vite (Node 22.12 ou superior na linha 22). Confira as versões antes de instalar.

```bash
php -v
composer --version
node --version
composer install
npm ci --ignore-scripts --no-audit --no-fund
```

Crie `.env` a partir de `.env.example` apenas se ainda não existir. No Linux/macOS:

```bash
cp -n .env.example .env
```

No Windows, copie o arquivo pelo editor ou explorador, preservando qualquer `.env` já existente. Edite estas opções no arquivo:

```dotenv
APP_ENV=local
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=sqlite
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
MAIL_MAILER=log
EMAIL_VERIFICATION_REQUIRED=false
ACCOUNT_INVITATION_DELIVERY=manual
REGISTRATION_ENABLED=false
CYCLE_TIMEZONE=America/Sao_Paulo
```

Remova ou comente a linha `DB_DATABASE=ideias_corp` do exemplo: sem essa variável, SQLite usa `database/database.sqlite`. Remova também um eventual `DB_URL`, para não conectar outro banco por engano. Habilite `IDEA_ATTACHMENTS_ENABLED=true` somente se quiser testar documentos privados neste ambiente de QA.

Crie o arquivo SQLite vazio somente se ele ainda não existir. No Linux/macOS:

```bash
touch database/database.sqlite
```

Para uma instalação nova, gere a chave, aplique as migrations e compile a interface:

```bash
php artisan config:clear
php artisan key:generate
php artisan migrate
npm run build
```

Escolha uma das alternativas de dados:

- **Demonstração local com dados fictícios:** `php artisan db:seed`. O seeder local cria contas de demonstração; não use essa opção para uma votação real.
- **Base vazia com administrador próprio:** `php artisan db:seed --class=AccessControlSeeder`, depois `php artisan app:create-administrator SEU_EMAIL --name="SEU_NOME"`. O comando pede a senha sem mostrá-la. Cadastre as demais pessoas na interface.

Inicie:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Abra `http://127.0.0.1:8000`. Esse endereço é local; não é publicação na internet. A primeira alternativa recria dados fictícios, não recupera as ideias e votos existentes no computador anterior.

## Se quiser manter os dados atuais

É um caminho diferente da base nova descrita acima. Não rode seeders sobre o banco que deseja preservar e não use `migrate:fresh`. Antes da transferência, suspenda gravações e use o procedimento de backup já documentado em `README.md` (`php artisan app:backup`) para copiar banco e documentos privados de forma consistente. O backup não inclui o `.env` nem `APP_KEY` e não é criptografado.

Transfira o backup por um meio privado protegido. Preserve a chave original separadamente: não execute `key:generate` sobre uma instalação restaurada. No destino, valide a cópia com `app:check-backup-restore` em um destino novo terminado em `_test`, conforme o procedimento de SQLite/MySQL do projeto. O comando de conferência usa uma cópia isolada e não configura automaticamente o banco principal. Aponte a instalação somente para a base que deseja usar e recoloque os documentos no armazenamento privado. Se necessário, peça ao Codex para conduzir a restauração lendo os comandos existentes e conferindo os caminhos antes de gravar.

Não envie `.env`, credenciais ou links individuais de primeiro acesso pelo chat. Evite usar a mesma base copiada em dois PCs com gravações independentes: as alterações não se sincronizam e o Git não mescla bancos.

## Retomar em um novo chat

Abra a pasta do projeto no Codex do PC do trabalho e envie o texto de [RETOMAR_NO_CODEX.md](RETOMAR_NO_CODEX.md). Ele aponta para os registros permanentes, sem exigir que esta conversa apareça no outro computador.

Leia nesta ordem:

1. `STATUS.md`: progresso e limitações.
2. `docs/ENTREGA_VOTACAO_SEXTA.md`: escopo da rodada e acesso manual.
3. `docs/CICLO_SELECAO.md`: fluxo de seleção de ideias.
4. `docs/VALIDACAO_LOCAL.md`: testes executados e limites.
5. `docs/ROTEIRO_QA.md`: conferência funcional.

## Verificações no computador novo

```bash
php artisan test --compact
npm test
npm run build
```

A suíte PHP tem 211 testes e a JavaScript tem três na última validação registrada. Isso descreve o computador anterior: execute novamente no novo ambiente. `npm run test:browser` usa banco e arquivos descartáveis, mas requer Google Chrome ou o executável configurado em `QA_CHROME_EXECUTABLE`.

As migrations vão com o código, porém só alteram o banco quando executadas. A hospedagem ainda não foi contratada nem configurada.
