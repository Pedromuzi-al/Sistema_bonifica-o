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

O anonimato é perante os demais colaboradores, não perante os avaliadores e administradores autorizados. Antes de produção, a empresa ainda deve definir finalidade, retenção, acesso e procedimentos de atendimento aos direitos previstos na LGPD.

## Estado atual e próximos passos

Já existem autenticação, papéis/permissões, estrutura de dados para o fluxo de ideias, telas iniciais de funcionário/avaliação/administração e um primeiro fluxo de votação. A autorização de leitura, a separação entre ideias públicas e privadas e a proteção dos painéis foram reforçadas e cobertas por testes.

A estrutura ainda não equivale a uma implementação completa da especificação. Próximas etapas prioritárias:

1. Completar o fluxo de status com transições permitidas, justificativas obrigatórias e histórico imutável.
2. Extrair criação, avaliação e mudança de status para Requests/Actions transacionais.
3. Implementar registro e validação dos resultados reais, com evidências e distinção entre estimativas e medições.
4. Completar a votação com critérios e pesos versionados por ciclo, bloqueio de voto próprio/duplicado e cálculo centralizado.
5. Implementar cálculo, aprovação, auditoria e divulgação de bonificações com regras versionadas.
6. Completar dashboards, notificações privadas, relatórios/exportações e controles de anexos.
7. Ampliar testes de autorização, concorrência, cálculo e privacidade de ponta a ponta.

Até essas etapas serem concluídas, não trate telas ou dados de demonstração como processo de negócio pronto para produção.

## Instalação local

Requisitos: PHP 8.3+, Composer, Node.js/npm e MySQL.

```sh
composer install
cp .env.example .env
```

Configure no `.env` o acesso ao banco MySQL (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD`) e então execute:

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

Os testes usam SQLite em memória, independentemente do banco MySQL de desenvolvimento. Os casos de fluxo de ideias verificam cadastro, voto duplicado, ocultação da autoria e bloqueio de acesso a propostas privadas e painéis restritos.
