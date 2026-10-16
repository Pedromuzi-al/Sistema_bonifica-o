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

O modelo atual reconhece uma autoria individual e uma categoria por ciclo. Anexos continuam desabilitados. Retenção definitiva, anonimização sob solicitação e integração com pagamento dependem de política e procedimentos corporativos específicos. Não configure exclusão automática sem definir essa política.

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

## Administração e primeiro acesso

Na administração, cadastre a pessoa com e-mail, setor, equipes e perfil de acesso. O sistema gera uma senha aleatória desconhecida e envia um convite: o titular define a própria senha pelo link, confirmando também seu e-mail. O link usa o token de recuperação, expira conforme `auth.passwords.users.expire` (60 minutos por padrão) e só pode ser usado uma vez. Convites pendentes podem ser reenviados na gestão da pessoa ou por “Esqueci minha senha”; contas bloqueadas não concluem o convite. O cadastro público existente continua criando contas sem perfil, que dependem de liberação administrativa.

Contas desativadas podem ter seus acessos revisados antes da reativação. Trocar o e-mail invalida as sessões e o acesso persistente anteriores; uma nova confirmação é necessária. A recuperação de senha também revoga sessões anteriores. Na edição do próprio perfil, a sessão atual é preservada para permitir a confirmação do novo endereço.

Antes de usar os convites, execute as migrations, configure o transporte de e-mail e teste a entrega em uma caixa real. Os testes automatizados usam notificações simuladas.
