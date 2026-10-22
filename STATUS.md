# Status do desenvolvimento local

Atualização: 22/10/2026 (Brasília). Prazo informado: novembro de 2026, ainda sem dia definido. Regras financeiras e seleção de modelos implementadas; Tailwind 4 migrado e auditoria npm sem alertas. Homologação externa e decisões operacionais permanecem pendentes. As datas e decisões anteriores abaixo são histórico.

## 22/10/2026 — Regras financeiras e modelos de avaliação por ciclo

O usuário pediu substituir o bloco “Reconhecimento pela ideia e regras adicionais” por dois seletores e retirar critérios do menu Administração. Após a explicação, escolheu explicitamente um modelo com várias perguntas e pesos.

### Implementado

- Novo grupo lateral **Regras financeiras**, com seta, animação, estado atual e abertura por teclado, seguindo o comportamento de Administração. Submenus: visão geral, Critérios técnicos e Regras de bonificação. Critérios foram retirados dos atalhos/cartões da Administração.
- **Critérios técnicos:** modelos reutilizáveis com nome, orientação, até vinte perguntas e pesos positivos que somam 100%. O cadastro sugere as três perguntas anteriores; o administrador pode ajustar, adicionar e remover. Busca, filtro de situação, edição, inativação e auditoria. Os critérios individuais usados em pareceres anteriores permanecem acessíveis em seção própria, sem converter ou apagar avaliações.
- **Regras de bonificação:** nome, descrição, valor por ideia, nota mínima, abstenção e quantidade de votos idênticos para alerta. Valor zero permite seleção sem dinheiro. Busca, filtro, edição, inativação e auditoria. O limite de ideias e o mínimo de votos continuam no ciclo; valores seguem sujeitos à seleção, aprovação e divulgação.
- **Criar ciclo de seleção:** dois seletores obrigatórios, com prévia das perguntas/pesos e das condições financeiras. Somente registros ativos são aceitos. O servidor usa as configurações cadastradas e ignora tentativas de substituir valor, nota ou perguntas por campos avulsos enviados na requisição. As escolhas são preservadas depois de erro de período.
- A criação do rascunho copia nomes, descrições, perguntas, pesos e parâmetros financeiros sob transação e bloqueio dos cadastros. A abertura preserva essa cópia; editar/inativar o cadastro não altera o rascunho anterior nem ciclos abertos. Ciclos antigos e o formulário de reconhecimento por resultados comprovados continuam com seu comportamento.
- Duas tabelas novas, `technical_models` e `bonus_policies`, separadas de `bonus_rules`, que guarda regras históricas associadas a prêmios. As migrations foram aplicadas no MySQL local dedicado após backup em `storage/app/backups/20261022-132633-elxJoWh9`. Nenhum dado de demonstração foi inserido no banco operacional. Os cadastros novos começam vazios para a administração definir suas regras.

### Validação

- SQLite: **254 testes / 1.649 verificações** aprovados.
- MySQL exclusivo de testes: **254 testes / 1.651 verificações** aprovados.
- **3 testes JavaScript**, Pint, build e `git diff --check` aprovados.
- Chrome isolado: cadastro de modelo, adição/remoção de perguntas, cadastro/edição de regra, menu com seta, seletores/prévias, ciclo com voto/apuração/decisão, temas claro/escuro e celular. Sem erros JavaScript ou HTTP 500 monitorados. Evidências: `storage/app/qa-reports/2026-10-22T13-26-52.870Z/resultado.json` e capturas `*-financeiro-*`.
- Novos testes cobrem permissão administrativa, valores inválidos, soma de pesos, perguntas duplicadas, regra/modelo inativos, campos financeiros forjados, erros de formulário e preservação de regras na abertura após editar/inativar os cadastros.

Não houve alteração de credenciais, commit, push, contratação ou publicação externa nesta entrega. As pendências operacionais de produção permanecem as descritas abaixo.

## 22/10/2026 — migração aprovada para Tailwind 4

O usuário autorizou explicitamente a migração, adaptação do CSS/Vite/PostCSS e validação das telas. As alterações de segurança e acesso da etapa anterior foram preservadas, junto às demais modificações que já estavam na branch `novo`.

### Entrega

- `tailwindcss` e `@tailwindcss/vite` em **4.3.3**, com uma única versão deduplicada na árvore. `@tailwindcss/forms` em **0.5.11**. Removidas as dependências diretas de Autoprefixer/PostCSS que serviam ao pipeline anterior; Vite mantém suas próprias dependências transitivas.
- Integração Vite ativa em `vite.config.js`. A configuração agora está em `resources/css/app.css`: `@import`, fontes explícitas, plugin de formulários, variante escura e tokens da paleta existente. `postcss.config.js` e `tailwind.config.js` antigos foram removidos.
- Templates adaptados às classes da versão 4: sombras, contornos acessíveis, raios pequenos, desfoque e transparência de anel. As pilhas verticais existentes usam utilitários próprios `stack-*` para conservar o espaçamento de formulários com tokens ocultos e rótulos em linha, sem sobrescrever o novo comportamento nativo de `space-y-*`.
- Overrides do tema escuro foram movidos para a camada de utilitários: a comparação visual detectou que a camada de componentes perdia prioridade para as classes geradas na versão 4. O teste de navegador agora verifica também o contraste básico de títulos em superfícies lisas; gradientes e superfícies muito transparentes permanecem para inspeção visual.
- A paleta da plataforma foi mantida. Variáveis e estados de foco foram conferidos; nenhuma regra de autenticação, permissão, voto, cálculo ou banco foi alterada por esta migração.
- `npm ci --ignore-scripts` reproduziu a instalação pelo lockfile. `npm audit` completo e `npm audit --omit=dev` retornaram **zero vulnerabilidades**. **QA-08 resolvido nesta verificação**, sem `npm audit fix --force` e sem exceções/overrides para ocultar avisos.

### Validação

- Captura anterior em Tailwind 3: `storage/app/qa-reports/2026-10-22T11-33-01.930Z/`.
- **248 testes PHP / 1.576 verificações**, **3 testes JavaScript**, Pint, build e conferência de whitespace aprovados.
- Chrome: login, cadastro e recuperação nos dois temas/desktop/celular, áreas administrativas, primeiro acesso direto, ideias, revisão, voto, apuração, decisão, relatórios, documentos, modais e teclado. Execução final: `storage/app/qa-reports/2026-10-22T11-48-35.024Z/resultado.json`, com lista de erros vazia.
- Comparativo final: `storage/app/qa-reports/2026-10-22T11-48-35.024Z/migration-comparison.json`. Nos **44 cenários**, foram conferidas **2.826 propriedades de medidas/tipografia** sem diferenças e **942 cores**, sem diferenças estáveis após normalização no Chrome. Três diferenças de borda por captura em instantes diferentes da transição de autofocus estão listadas separadamente. Há **50 verificações básicas de contraste** aprovadas. Raios de cápsulas e serialização de sombras foram inspecionados visualmente. A amostragem não equivale a certificação de acessibilidade ou igualdade de todos os pixels de todas as telas.

### O que ainda falta para a entrega do projeto

Escolher o destino de produção e homologar HTTPS, tarefas agendadas, armazenamento privado, restauração MySQL/arquivos e carga no servidor escolhido; testar SMTP somente se ativado. Confirmar os responsáveis, regras de reconhecimento, retenção e a data específica de novembro com a empresa. Não houve contratação, publicação, alteração do `.env`, migration, exclusão de dados reais, commit ou push nesta migração.

## 21/10/2026 — fechamento das lacunas confirmadas

O prazo urgente de outubro foi substituído por novembro de 2026. Nenhuma data final específica, hospedagem ou contratação foi definida nesta continuidade. A revisão partiu da branch `novo`, limpa, com **234 testes PHP / 1.451 verificações** aprovados.

### Correções implementadas

- **QA-01:** a troca da própria senha usa transação e revalida a senha atual no registro bloqueado. Revoga sessões e links antigos, rotaciona o token persistente e incrementa a versão de acesso. A sessão que realizou a operação recebe novo identificador e nova versão; sessões antigas são recusadas. A auditoria não guarda a senha nem seu hash.
- **QA-02:** alterar o próprio e-mail exige a senha atual. Alterar somente o nome não exige senha. A regra é repetida dentro da transação para não depender de uma validação feita antes de uma alteração concorrente. O caminho administrativo mantém sua autorização e auditoria. A confirmação do novo endereço continua dependendo da configuração de e-mail; não há alegação de confirmação automática.
- **QA-03:** confirmação de senha, troca da própria senha e atualização do perfil têm limite de cinco requisições por minuto, por conta, IP e caminho. Funcionários diferentes no mesmo IP não compartilham o limite autenticado. Tipos inválidos nos campos de senha retornam validação, sem erro 500.
- **QA-04:** autocadastro habilitado atribui somente o perfil `employee`, em transação, ignorando tentativas de obter perfil administrativo. Exige a configuração prévia de acesso pelo `AccessControlSeeder`; se ela estiver ausente, retorna indisponibilidade sem criar uma conta parcial. O autocadastro continua desativado no modelo de produção.
- **QA-05:** medições não numéricas/não finitas e operações com estouro na diferença ou no percentual retornam 422. Uma entrada inválida não substitui um resultado anterior. Não foram impostos limites monetários ou unidades de negócio arbitrários.
- **QA-06:** retornar da validação para implementação remove a validação anterior e reabre o plano, preservando os estados anteriores na auditoria. Concluir exige validar novamente os resultados.
- **QA-07:** recuperação por e-mail usa a mesma mensagem pública para endereço existente, inexistente e token já solicitado, e tem limite por IP. Falhas no transporte são registradas internamente sem mudar essa resposta pública.
- **Primeiro acesso:** o administrador também pode definir diretamente a primeira senha em Pessoas e acessos. `Redefinir senha` abre Nova senha/Confirmar senha; `Concluir` salva e `Cancelar` limpa os campos. A conta deixa de estar pendente, sem marcar o e-mail como verificado. Contas indisponíveis continuam protegidas. O fluxo opcional de convite por e-mail permanece disponível; a interface manual não depende de gerar links.
- **Formulários independentes:** um erro na senha não desmarca as equipes/permissões existentes; o formulário de dados ainda preserva opções desmarcadas quando ele próprio contém erros.

Os testes de regressão de segurança estão em `tests/Feature/SecurityHardeningTest.php` e `tests/Feature/ResultIntegrityTest.php`, além das coberturas anteriores atualizadas para o comportamento novo. `docs/qa/AuditFindingsTest.php` permanece como reprodução histórica do estado vulnerável, não como requisito de aceitação.

### Situação do prompt original

| Etapas do plano original | Estado atual |
|---|---|
| 1–2: arquitetura, banco, autenticação e administração | Implementados; lacunas de acesso acima corrigidas localmente |
| 3–8: ideias, avaliação, execução, publicação, votação e reconhecimento | Implementados e exercitados pelos testes; seleção antes da implementação preservada conforme decisão posterior do usuário |
| 9: painéis, relatórios e notificações | Implementados; relatórios por ciclo e bonificações com permissões e snapshots |
| 10: auditoria e segurança | QA-01 a QA-07 corrigidos em 21/10; QA-08 resolvido pela migração autorizada de 22/10 |
| 11: testes | SQLite, MySQL dedicado, JavaScript e navegador conferidos nesta continuidade; ver resultados abaixo |
| 12: documentação e operação | Instruções de acesso atualizadas; homologação externa e decisões operacionais pendentes |

### Validação desta continuidade

- SQLite: **248 testes / 1.576 verificações**, todos aprovados, incluindo requisições com tipos inválidos nos campos de senha.
- MySQL dedicado `127.0.0.1:3307/ideias_corp_test`: **248 testes / 1.578 verificações**, todos aprovados na execução final.
- JavaScript: **3 testes aprovados**.
- Chrome/Playwright: aprovado, com primeiro acesso direto, cancelamento/limpeza, erro de confirmação e login, mais os percursos de revisão, voto, apuração, decisão, relatórios, privacidade e celular/teclado. Sem erro JavaScript ou HTTP 500 nos percursos monitorados. Evidências em `storage/app/qa-reports/2026-10-21T20-01-43.807Z/resultado.json`.
- Vite, Pint e `git diff --check`: aprovados.
- `composer audit` e `npm audit --omit=dev`: sem advisories reportados. A auditoria npm completa continua com os alertas de ferramentas descritos em QA-08.

### Pendências reais para finalizar

1. **QA-08 / build — resolvido em 22/10:** migração para Tailwind 4 autorizada pelo usuário e concluída; auditoria npm completa sem vulnerabilidades. Consulte a atualização acima. A exigência anterior de autorização foi atendida.
2. **Decisões da empresa:** confirmar data de novembro, responsáveis, regras/valores de reconhecimento e política de retenção/preservação. O sistema permite configurar essas regras; não inventar decisões ou prazos corporativos.
3. **Destino de produção:** escolher hospedagem, configurar domínio/HTTPS, banco dedicado, arquivos privados e execução agendada; validar SMTP somente se a modalidade por e-mail for ativada. Nenhum serviço foi contratado ou publicado.
4. **Homologação:** ensaio com contas dos três perfis e os responsáveis, carga no ambiente de destino e restauração real de backup MySQL/arquivos nesse ambiente. As validações locais e a restauração SQLite isolada não comprovam esses itens externos.

Não houve migration nova, alteração do `.env`, seeding no banco operacional, exclusão de dados reais, commit, push ou deploy nesta continuidade.

## Subtarefas

- [x] Identificar o estado atual e preservar as alterações existentes na branch.
- [x] Revisar permissões, validação e continuidade do processo de ciclos.
- [x] Revisar navegação por teclado, menu no celular e mudanças de tamanho da tela.
- [x] Corrigir os problemas confirmados e ampliar os percursos automatizados.
- [x] Executar a suíte PHP, os testes JavaScript e a formatação.
- [x] Concluir a última execução do navegador e registrar as evidências.
- [x] Fechar este relatório com os resultados finais.

## Funcionalidade entregue nas etapas anteriores

- Votações abre a listagem dos ciclos com nome, tipo, período, situação e acesso ao detalhe.
- A listagem mostra uma mensagem quando não há ciclos. Funcionários veem somente ciclos abertos ou encerrados.
- O botão Novo ciclo, disponível para administradores, abre `/cycles/create`. A criação não fica mais misturada à listagem.
- A tela de criação mantém os tipos Seleção de ideias e Reconhecimento por resultados comprovados e preserva os campos quando há erro.
- O menu principal fica à esquerda no desktop. No celular, abre como uma gaveta. Perfil, tema e permissões continuam funcionando.
- O ciclo de seleção aceita propostas antes da execução: triagem e revisão da publicação → votação no potencial → decisão da equipe → plano e execução. Ideias não selecionadas ficam no banco. A bonificação pela ideia é opcional; resultados podem ser reconhecidos depois.

## O que foi corrigido nesta continuidade

### 1. Ideias indisponíveis na escolha de propostas

**Problema:** o formulário mostrava como selecionáveis ideias que já participavam de outra votação ou aguardavam decisão no ciclo anterior. O servidor recusava a confirmação, mas o impedimento só aparecia depois da tentativa.

**Correção:** foi criada a regra compartilhada `SelectionCycleService::participationBlocker()`. Ela é consultada pela tela e pelos serviços de abertura dos dois tipos de ciclo. O formulário agora desabilita a opção e explica o motivo. Ideias indisponíveis também são removidas da seleção restaurada depois de um erro.

**Preservação:** o servidor continua validando na transação de abertura, depois de bloquear os registros. Uma tela aberta há algum tempo não permite contornar uma mudança posterior de disponibilidade. Uma decisão pendente de seleção também impede iniciar o reconhecimento por resultados antes de resolver essa pendência.

**Retorno à disponibilidade:** depois que a equipe registra a decisão anterior, a proposta pode ser incluída novamente, desde que cumpra os demais requisitos do tipo de ciclo.

**Arquivos:** `app/Services/SelectionCycleService.php`, `app/Services/CycleService.php`, `app/Http/Controllers/CycleController.php`, `resources/views/cycles/show.blade.php` e `tests/Feature/SelectionCycleTest.php`.

### 2. Foco de teclado ao mudar entre celular e desktop

**Problema:** ao redimensionar a janela, o controle que estava focado podia ficar oculto. O navegador pode remover o foco antes de disparar o evento da mudança de tamanho.

**Correção:** o layout registra o último elemento focado. Ao passar para o celular, devolve o foco ao botão do menu quando a lateral fica oculta. Ao passar para o desktop, transfere o foco do botão oculto para o item ativo do menu. Campos do conteúdo que continuam visíveis mantêm seu foco.

**Complemento:** a barra superior também fica indisponível para interação enquanto a gaveta está aberta, assim como o conteúdo principal. Fechar o menu restaura a interação e o foco. Escape, clique no fundo e navegação circular por Tab/Shift+Tab estão incluídos no teste de navegador.

**Arquivos:** `resources/js/app.js`, `resources/views/layouts/app.blade.php` e `resources/views/layouts/navigation.blade.php`.

### 3. Ordem estável da listagem

A listagem passou a ordenar pelo identificador mais recente. Isso mantém uma ordem determinística quando dois ciclos são criados no mesmo segundo e evita depender de desempates implícitos do banco durante a paginação.

**Arquivo:** `app/Http/Controllers/CycleController.php`.

### 4. Testes ampliados

O roteiro automatizado do Chrome foi ampliado para verificar:

- proposta bloqueada durante outra votação, bloqueada enquanto a decisão anterior está pendente e liberada após a decisão;
- fundo e barra superior indisponíveis enquanto o menu está aberto;
- foco ao alternar entre 1023 e 1024 px, inclusive com o menu aberto;
- fechamento pelo fundo e por Escape;
- navegação circular por teclado no menu;
- erro de período na criação, preservação dos valores e retorno à listagem;
- capturas do menu em 320/390 px e do motivo de indisponibilidade da proposta.

Os testes usam banco e armazenamento descartáveis. Apenas os limites de tempo do ciclo da base descartável são ajustados para evitar uma espera real pelo período de votação. O relógio e os ciclos da base de trabalho não são alterados por essa automação.

## Resultados de validação

| Conferência | Resultado disponível |
|---|---|
| Suíte PHP em SQLite | 196 testes aprovados, 1.116 verificações; repetição final aprovada |
| JavaScript do cadastro por etapas | 3 testes aprovados |
| Formatação PHP | Pint aprovado |
| Build Vite | Concluído na execução do teste de navegador |
| Sintaxe JavaScript e templates Blade | Sintaxe aprovada; templates compilados e cache temporário removido |
| Migrations locais | Todas aplicadas; nenhuma migration nova nesta continuidade |
| Navegador | Aprovado em Chrome, sem erros JavaScript ou HTTP 500 nos percursos monitorados |

A revisão independente de código confirmou a correção da disponibilidade e não encontrou uma nova falha concreta de autorização ou de continuidade do fluxo de seleção até reconhecimento. Isso complementa os testes, sem substituir seu QA exploratório.

## Evidências e comandos

Execução aprovada: `storage/app/qa-reports/2026-10-18T21-29-47.934Z/`.

- `resultado.json`: resultado final e cenários executados, com lista de erros vazia.
- `proposta-indisponivel.png`: motivo do bloqueio antes de tentar abrir outro ciclo.
- `menu-celular-320.png` e `menu-celular-390.png`: menu no celular.
- `listagem-ciclos.png`: listagem separada da criação.
- `desktop-claro.png`, `desktop-escuro.png` e `avisos-escuro.png`: temas e contraste.
- `selecao-antes-implementacao.png`: decisão da equipe depois da votação.

Comandos executados com sucesso na pasta do projeto:

```sh
vendor/bin/pint --dirty --test
php artisan test
npm test
npm run test:browser
node --check resources/js/app.js
php artisan view:cache
php artisan view:clear
php artisan migrate:status
git diff --check
```

`npm run test:browser` executa o build e cria seu próprio ambiente descartável. A aplicação utiliza o Chrome instalado no sistema; outro executável pode ser informado por `QA_CHROME_EXECUTABLE`, conforme o script. Não aponte os testes para a base de trabalho. As primeiras execuções ampliadas ajudaram a identificar o comportamento de foco ao redimensionar; o resultado aprovado acima já inclui a correção.

## Como avaliar quando voltar

1. Inicie a aplicação local como de costume e atualize o navegador. O build já estará preparado.
2. Entre como administrador e abra Votações. Confira a listagem e use Novo ciclo para abrir o cadastro separado.
3. Crie um ciclo de seleção com uma proposta já revisada. Crie outro rascunho e confira que a mesma proposta aparece desabilitada, com o motivo do impedimento.
4. Apure o primeiro ciclo: a proposta deve continuar indisponível no segundo enquanto a decisão estiver pendente. Registre a decisão e recarregue o segundo rascunho para conferir a liberação.
5. No celular ou na emulação do navegador, abra o menu, navegue com Tab/Shift+Tab e feche com Escape ou pelo fundo. Alterne entre larguras de 1023/1024 px e confirme que o foco continua em um controle visível.
6. Provoque um erro de período na criação de um ciclo e confira que os dados continuam preenchidos. Depois use Voltar para os ciclos.
7. Repita os percursos no tema claro e no escuro, com contas de administrador, avaliador e funcionário.

Use também `docs/ROTEIRO_QA.md` e `docs/CICLO_SELECAO.md` para os demais cenários.

## Estado da entrega e limites

- As alterações permanecem na branch local, junto às modificações anteriores. Não foram realizados commit, merge ou deploy.
- O `.env` não foi modificado nesta continuidade. Não foram cadastradas contas ou ciclos de QA na base de trabalho.
- Não houve exclusão de dados, execução de seeder na base de trabalho ou alteração das regras de ciclos já abertos.
- A publicação permanece adiada conforme sua decisão anterior. Hospedagem, domínio, HTTPS externo e serviço de e-mail ainda precisam ser escolhidos e validados.
- A suíte atual foi validada em SQLite. MySQL e entrega real de e-mail não foram validados nesta continuidade.
- QA com pessoas, acessibilidade exploratória, testes de carga e homologação continuam pendentes; os testes locais não substituem essas etapas.


## Atualização posterior — Administração por áreas

Foram separados os acessos de Setores, Pessoas e acessos, Critérios técnicos e Equipes por cartões no painel administrativo. O formulário de critérios e a listagem resumida de pessoas foram retirados do painel principal; cada área tem sua própria tela.

- Setores: tabela, busca, filtro ativo/inativo, botão Novo setor e detalhe com pessoas vinculadas/não vinculadas/todas. Transferência e remoção do setor de referência exigem confirmação, autorização administrativa e auditoria. Não alteram equipes, permissões ou setores das ideias históricas. Setores inativos não recebem novos vínculos. Contas desativadas são exibidas apenas para consulta.
- Pessoas e acessos: tabela de pessoas, setor/equipes, perfil, permissões efetivas e situação, com acesso ao formulário já existente de gestão da conta.
- Critérios técnicos: listagem, busca, filtro de situação, botão de criação e formulário de edição. Criação/edição voltam à listagem; as avaliações anteriores preservam suas descrições, pesos e notas.
- Equipes: mantidas em área própria, sem misturar a lista de setores.
- Menu lateral: atalhos das áreas aparecem dentro de Administração, com indicação da área atual.

Validação PHP desta alteração: 202 testes e 1.173 verificações aprovados. O roteiro de QA humano está na seção 18 de `docs/ROTEIRO_QA.md`. O Chrome aprovou o cadastro do setor, vínculo e remoção de pessoa, inativação do setor, cadastro/edição/inativação de critério e as novas telas nos dois temas. Também conferiu ausência de rolagem horizontal da página em 320/390 px. Evidências: `storage/app/qa-reports/2026-10-18T21-44-06.111Z/`, com `setor-pessoas-vinculadas.png`, `criterios-listagem.png` e `resultado.json`. Não houve erro JavaScript ou HTTP 500 nos percursos monitorados. Pint, compilação Vite e templates Blade também passaram. Não há nova migration nesta alteração.


## Atualização posterior — Visual e animações adaptados

A adaptação usa a identidade Conted Tech e mantém Blade/Tailwind/Alpine. A área autenticada passou a ter fundos e superfícies mais leves, sombras discretas e botões/cantos consistentes. O Painel recebeu apresentação de boas-vindas e indicadores com ícones, cores por situação e um destaque azul. O novo componente `resources/views/components/stat-card.blade.php` é compartilhado com os indicadores da Administração. Valores e permissões continuam vindo das regras anteriores; não foram inventadas métricas, porcentagens ou tendências.

Os efeitos adicionados são entrada suave dos indicadores por opacidade em sequência, subida de 5 px no hover somente desses indicadores com mouse, abertura/fechamento suave da gaveta e desenho das linhas SVG dos relatórios. Reduzir movimento mantém os indicadores sem deslocamento/entrada animada, os gráficos completos e a gaveta sem transição. Dispositivos de toque não recebem a subida por hover. Cartões de conteúdo, formulários e tabelas permanecem parados.

Arquivos principais: `resources/css/app.css`, `resources/views/dashboard.blade.php`, `resources/views/admin/index.blade.php`, `resources/views/components/stat-card.blade.php`, `resources/views/components/metric-chart.blade.php`, `resources/views/layouts/app.blade.php`, `resources/views/layouts/navigation.blade.php` e `scripts/qa-browser.mjs`. Nenhuma dependência ou migration foi acrescentada; `.env` não foi alterado.

A suíte PHP passou com 202 testes e 1.173 verificações, e os três testes JavaScript do formulário também passaram. O Chrome aprovou o hover de 5 px, o retorno à posição inicial e a desativação das animações com Reduzir movimento nos dois temas. Também aprovou os indicadores sem hover em contexto de toque, o gráfico completo com movimento reduzido e as telas em 320/390 px, incluindo o Painel. A execução completa preservou os percursos anteriores de administração, cadastro, documentos e ciclos, sem erros JavaScript ou HTTP 500. Evidências: `storage/app/qa-reports/2026-10-18T23-46-58.353Z/`, incluindo `desktop-claro.png`, `desktop-escuro.png`, `painel-celular-escuro.png` e `resultado.json`. Build Vite, Pint, templates Blade e `git diff --check` também passaram.


## Rodada até sexta: acesso manual e hospedagem gratuita

### Decisões recebidas

- Premiar a ideia selecionada mesmo antes da implementação.
- Valor por ideia e limite de selecionadas configuráveis: já disponíveis na criação do ciclo de seleção; nenhum valor real foi definido pelo agente.
- Acesso dos funcionários por endereço na internet.
- Sem exigência de confirmação e envio de e-mail nesta primeira versão.
- Sem hospedagem/domínio contratado e sem servidor disponível na empresa. Avaliar serviço externo gratuito.
- Hikaro e Thalita são possíveis responsáveis; contas, perfis e substituições em caso de participação na proposta ainda não foram confirmados.

### Subtarefas desta etapa

- [x] Tornar a verificação de e-mail configurável, preservando o modo estrito.
- [x] Concluir cadastro, primeiro acesso e recuperação sem transporte de e-mail.
- [x] Exigir confirmação da senha do administrador, justificativa e limitação de requisições para gerar links.
- [x] Preservar perfis, bloqueios, sessões e proteção do último administrador.
- [x] Ajustar interfaces, filtros e orientação em Esqueci minha senha.
- [x] Validar caminhos manual/estrito com suíte PHP e primeiro acesso real no Chrome isolado.
- [x] Pesquisar limitações oficiais das alternativas gratuitas e registrar plano operacional.
- [ ] Escolher/criar conta do serviço externo e comprovar disponibilidade gratuita.
- [ ] Homologar banco, HTTPS, acesso por outra rede, backup e restauração no destino.
- [ ] Criar contas reais e concluir QA com os responsáveis e funcionários.

### O que mudou e como foi feito

`EMAIL_VERIFICATION_REQUIRED=false` e `ACCOUNT_INVITATION_DELIVERY=manual` são os padrões novos em `config/innovation.php` e nos modelos de ambiente. O middleware configurável mantém a exigência do Laravel quando ativada e permite acesso quando dispensada. O model continua registrando a verificação real; não finge confirmar endereços. Gate administrativo e proteção do último administrador consideram a configuração e exigem primeiro acesso concluído. O middleware de contas também rejeita sessões de convites pendentes.

No cadastro manual, a pessoa recebe uma senha aleatória desconhecida e fica pendente. Em Gerenciar acesso, o administrador confirma a própria senha, retorna à conta, informa justificativa e gera um link. A rota utiliza o broker de senhas do Laravel: token com hash no banco, prazo configurável, renovação que invalida o anterior e uso único. A auditoria guarda responsável, finalidade e motivo, sem o token. O link aparece somente na resposta da geração e apenas na conta correspondente, com cache privado desativado.

Ao definir a senha pelo link manual, o titular é liberado sem alterar a verificação do e-mail. Sessões anteriores são revogadas. Recuperação de contas liberadas usa o mesmo mecanismo; Esqueci minha senha orienta procurar a administração. Atualizar o e-mail de uma conta pendente não tenta enviar convite. Contas bloqueadas/desativadas não recebem links; permissões não são ampliadas pelo acesso manual.

O comando de administrador inicial funciona sem SMTP quando a confirmação está desativada. A verificação de produção só exige SMTP quando confirmação ou entrega por e-mail estiverem habilitadas. Ela continua rejeitando as outras pendências de ambiente; não foi enfraquecida para declarar a máquina local pronta para produção.

O README foi atualizado e seus marcadores de conflito antigos foram removidos preservando as duas seções. O roteiro de QA e a entrega local foram atualizados. O guia novo [ENTREGA_VOTACAO_SEXTA.md](docs/ENTREGA_VOTACAO_SEXTA.md) concentra configuração, primeiro acesso, operação da rodada e comparação das hospedagens.

### Testes e evidências

- PHP/SQLite: **211 testes aprovados / 1.263 verificações**. Nove cenários novos de acesso manual; testes antigos mantêm a configuração estrita para evitar regressões nessa opção.
- JavaScript: **3 testes aprovados**.
- Pint e verificação de espaços/conflitos do diff: aprovados.
- Compilação de produção e navegador: aprovados. Evidências em `storage/app/qa-reports/2026-10-19T00-47-41.571Z/`; o horário do nome é UTC.
- Chrome executou primeiro acesso em contexto separado, login sem e-mail e autorização; repetiu setores/critérios, cadastro de ideia, documento privado, ciclo curto, triagem, voto, apuração e decisão, mobile/teclado/temas/redução de movimento. Tokens não aparecem nas capturas.
- A tentativa inicial do navegador precisou separar as sessões: a senha já confirmada no novo cenário dispensava a confirmação esperada no cenário antigo. Após corrigir o isolamento do teste, a execução completa passou.
- Configuração local efetiva confirmada como `false`/`manual`; nenhum `.env` foi sobrescrito e não houve migration nova nesta etapa.

### Hospedagem: conclusão da pesquisa, ainda sem deploy

A infraestrutura interna foi descartada pelo usuário. A candidata que preserva Laravel/MySQL é uma VM Oracle Always Free, condicionada à criação da conta, verificação por cartão quando exigida e disponibilidade regional. É preciso manter apenas recursos elegíveis gratuitos, configurar endereço HTTPS e operar backup fora da máquina. Não há garantia de obter uma VM antes de sexta.

Render Free não foi tratado como produção pronta: o fornecedor orienta uso de teste, o serviço dorme e o disco local é temporário. Neon fornece Postgres gratuito, mas exigiria adaptação/homologação do banco e backup. InfinityFree não oferece shell/Artisan, cron e workers; Quick Tunnel é temporário e destinado a testes. Fontes oficiais e detalhes estão no guia da rodada.

Não foram criadas contas externas, contratados recursos, expostos serviços locais ou publicadas credenciais. Permanecem pendentes MySQL, operação externa, QA humano e política de retenção/anonimização para registros em aberto/banco de ideias. O sistema registra o reconhecimento; o pagamento real acontece fora dele.


## Continuidade no PC do trabalho

O usuário pediu a preparação para continuar em outro computador, sem depender de sincronização do histórico do chat. Antes desta etapa, a árvore estava limpa na branch `adm`, commit `e39657f`, alinhada com a referência local `origin/adm`.

Foram acrescentados `docs/CONTINUAR_EM_OUTRO_PC.md`, com atualização da branch, instalação local nova em SQLite, distinção entre dados fictícios e restauração, verificações e proteção das credenciais; e `docs/RETOMAR_NO_CODEX.md`, com um texto pronto para o próximo chat recuperar decisões, prioridades e pendências. A pesquisa posterior passou a considerar hospedagem barata, além de gratuita. Nenhum fornecedor foi escolhido ou contratado.

A preparação desta etapa contém apenas documentação: banco, anexos, `.env` e configurações da máquina não foram transferidos. A referência de 211 testes PHP, três JavaScript e Chrome é da execução anterior; não houve nova mudança de comportamento que exigisse repeti-los. Os arquivos novos foram conferidos quanto a links locais e erros de diff. O resultado do envio ao Git é informado na resposta desta etapa; a sincronização da conversa em outro PC não foi configurada nem comprovada.


O envio desta documentação para `origin/adm` foi tentado, mas não concluído: o Git HTTPS não conseguiu obter o usuário por falta de autenticação neste ambiente. O commit permanece local. Foi preparado também um ZIP de código/documentação a partir do commit, fora do repositório, sem arquivos ignorados. Para enviar pelo repositório, autentique o Git pelo gerenciador de credenciais do editor e execute `git push origin adm`; não informe tokens ou senhas no chat.


## 19/10/2026 — alternativa 1: revisão simples entregue

O usuário aprovou simplificar a preparação das ideias para a rodada desta semana. A seção Revisar ideia passou para o início da página, com título, problema e solução preenchidos a partir do cadastro; benefícios e categoria são opcionais. Há três decisões: Liberar para votação, Pedir ajuste e Arquivar, todas com justificativa. A confirmação de privacidade é exigida somente para liberar.

Liberar passa diretamente de Enviada para Liberada para votação (`ready_for_voting`) e publica o texto revisado na mesma transação. Não exige triagem separada, avaliação técnica ou critérios com notas. Benefícios vazios são apresentados como ainda não estimados; categoria vazia usa Melhoria. A liberação não concede prêmio nem aprova a execução. A seleção posterior no ciclo continua sendo a decisão humana que pode aprovar a ideia e propor a bonificação configurada.

Pedir ajuste envia aviso privado aos participantes e mostra Precisa de ajuste. O autor responde na página da ideia; a resposta fica no histórico e retorna para Enviada, para nova revisão. O responsável usa a resposta para revisar o texto público antes de liberar. Arquivar preserva dados/histórico/auditoria, retira a publicação e a elegibilidade do ciclo, sem excluir a proposta.

O menu agora chama Revisão de ideias; a listagem resume Enviadas, Liberadas, Precisa de ajuste e Arquivadas, mantendo os filtros históricos. Andamento e avaliação técnica ficaram recolhidos numa seção opcional. O painel usa Aguardando revisão. Ideias antigas continuam com seus status e pareceres; não houve conversão em massa ou migration, pois o status já é texto.

Segurança: autorização e impedimento de autor/coautores são reconferidos no serviço após bloquear a ideia. Ações de revisão não podem modificar a situação durante ciclo aberto/programado ou enquanto a decisão anterior estiver pendente. Um parecer desfavorável anterior precisa ser resolvido antes da liberação. Histórico e notificações são gravados junto à decisão.

Validação: **215 testes PHP / 1.304 verificações aprovados**, quatro cenários novos; **três testes JavaScript aprovados**. Chrome isolado aprovado em `storage/app/qa-reports/2026-10-19T16-12-38.591Z/`, incluindo cadastro → liberação direta → voto → apuração → decisão, além das regressões de temas, mobile, acesso, setores e critérios. O teste PHP novo também confere a proposta de bonificação de R$ 100 fictícios sem parecer técnico. Os testes iniciais detectaram colisão de nome da rota com o resumo do cadastro; corrigida para `ideas.review-decision`, com execução completa seguinte aprovada.

Foram atualizados README, roteiro de QA, guia do ciclo, guia da rodada e texto de retomada. O banco/.env local não foi alterado; o usuário já pode retomar seu QA recarregando a página. Hospedagem, MySQL e homologação externa continuam pendentes.


## 19/10/2026 — botão Excluir ideias com seleção em pop-up

O usuário solicitou exclusão de ideias por seleção em pop-up. Foi acrescentado o botão Excluir ideias na listagem, somente para administração. O diálogo nativo mostra as ideias da página atual, respeitando filtros e paginação, com caixas para marcar e motivos de bloqueio. Há uma segunda etapa com quantidade, motivo obrigatório e confirmação explícita. Cancelar/Fechar/Escape não gravam dados; o foco é administrado pelo diálogo do navegador. Foram incluídas cores próprias por tema e limite de tamanho para celular.

A operação é uma exclusão administrativa das listagens (`SoftDeletes`), descrita explicitamente na interface, sem apagar definitivamente dados ou arquivos. O model preserva a vedação de exclusão definitiva. A rota valida permissão, IDs distintos/existentes, motivo e confirmação, com limite por requisição e de frequência. O serviço bloqueia registros em ordem e valida o lote inteiro antes de qualquer exclusão; grava histórico, auditoria por ideia e aviso privado aos participantes. Não é um mecanismo de anonimização ou retenção definitiva.

Não podem ser excluídas ideias associadas a qualquer ciclo/voto ou bonificação, com plano/resultados/etapa de execução, ou com documento em preservação obrigatória. A restrição vale também no servidor caso a situação mude depois de abrir o pop-up. Ideias removidas deixam de aparecer nas consultas normais e o vínculo direto retorna 404. Os registros permanecem no banco para preservação de histórico.

A migration `2026_10_19_170000_add_soft_deletes_to_ideas.php` adiciona `deleted_at` com índice. Antes de aplicá-la na base local, foi criado o backup `storage/app/backups/20261019-162354-DL5hxSDX`, restaurado e conferido em `storage/app/restore-check/before_idea_deletion_20261019_test`. A migration foi aplicada com sucesso. Nenhuma ideia do banco local foi excluída durante o desenvolvimento; os cenários de exclusão são executados em banco descartável.

Arquivos principais: `IdeaDeletionService`, `IdeaController`, `IdeaPolicy`, `Idea`, rota `ideas.bulk-destroy`, listagem e parcial `ideas/partials/bulk-delete`, CSS, migration e testes `BulkIdeaDeletionTest`. Roteiros de QA/entrega local atualizados. Resultado final: **220 testes PHP / 1.353 verificações**, três testes JavaScript, Pint, build e diff aprovados. Chrome aprovado em `storage/app/qa-reports/2026-10-19T16-24-49.349Z/`, incluindo pop-up claro/escuro no celular, foco/Escape, seleção, confirmação e retirada da listagem. Captura escura inspecionada visualmente. A conferência de migrations mostrou a nova migration aplicada no batch 8, sem pendências.


### Ajuste visual do botão de exclusão

A pedido do usuário, Excluir ideias e Nova ideia foram agrupados lado a lado, à direita do cabeçalho da listagem. O botão de exclusão passou a vermelho com texto branco, hover vermelho mais escuro apenas em dispositivos com mouse e foco visível para teclado nos dois temas. A alteração é apenas visual e não modifica a operação de exclusão nem requer migration.


### Painel e orientação para escolher ideias no ciclo

Removido somente o botão Nova ideia do cabeçalho do Painel corporativo, a pedido do usuário. O acesso Tenho uma ideia do cartão de boas-vindas e Nova ideia na listagem permanecem. A mensagem de ciclo sem propostas foi alinhada à revisão simples: orienta usar Liberar para votação, sem exigir triagem, implementação ou notas técnicas, e oferece atalho para Revisão de ideias. Não há migration nesta alteração.


## 19/10/2026 — animações na escolha das notas

A pedido do usuário, os cartões de notas da votação ganharam transições de cor, borda e sombra, breve realce de escala ao selecionar, entrada discreta das perguntas em sequência e entrada do resumo por opacidade. O hover levanta apenas opções ainda não selecionadas em dispositivos com mouse. O destaque visual é uniforme para notas 1 a 5, sem favorecer respostas altas. Foi retirado o contorno duplicado da seleção com mouse; o foco do teclado continua destacado no cartão inteiro. A preferência de reduzir movimento desativa animações, deslocamentos e transições. Nenhuma migration ou alteração do cálculo/envio de votos foi necessária.

A validação do navegador foi ampliada para conferir ausência de nota inicial, troca por setas, estilo nos dois temas, preferência de reduzir movimento e layout de celular, seguida do voto e apuração existentes. Compilação e Chrome aprovados. Evidências em `storage/app/qa-reports/2026-10-19T20-39-08.342Z/`, incluindo notas claro/escuro/celular, nenhuma resposta inicial, navegação por setas e foco, movimento reduzido sem transições, conferência/confirmação do voto e continuidade até apuração/seleção. O teste detectou uma regra global que ainda impunha uma duração mínima com movimento reduzido; a regra específica foi ajustada para desativar totalmente as transições das notas. Captura escura inspecionada visualmente e diff conferido. Não houve nova alteração de PHP/regras de negócio nem necessidade de repetir a suíte PHP nesta etapa visual.


### Explicação da votação sempre visível

O bloco Como os votos ajudam no reconhecimento? passou de um acordeão para uma seção fixa, com título e texto sempre visíveis e o link das regras preservado. Alteração apenas de marcação, sem migration ou mudança de votação.


### Auditoria retirada do menu e proposta de reformulação de relatórios/resultados

A pedido do usuário, Auditoria deixou de aparecer no menu lateral, inclusive no celular. O registro interno de ações e as permissões da rota existente foram preservados. O usuário solicitou três alternativas para a reformulação de Relatórios e da apresentação dos resultados de votação; as propostas estão sendo apresentadas para escolha antes de alterar essas duas telas. A preferência é por mais clareza e movimento visual, preservando privacidade, neutralidade durante a votação e redução de movimento.


## 19/10/2026 — alternativa 1 de relatórios e resultados entregue

O usuário aprovou a visão por ciclo e perguntou sobre a melhoria visual. Relatórios passou a abrir a rodada mais recente, com seletor de ciclo, tipo/período/situação e indicadores de ideias, pessoas únicas e votos válidos. Depois da apuração, exibe uma tabela de classificação com pontuação, quantidade de votos, decisão e estado da bonificação. Os dados usam os snapshots/resultados congelados; notas e ranking não aparecem durante a votação.

A página do ciclo compartilha a classificação e o resumo, recolhendo textos extensos e formulários nos detalhes de cada proposta. Ver detalhes abre a proposta correspondente e preserva o acesso às decisões e aos acompanhamentos. Alertas, impedimentos e aprovação/divulgação continuam com a validação anterior. A classificação não concede prêmio automaticamente.

A área financeira distingue propostas pendentes, total aprovado (incluindo divulgado) e divulgado, sem somar valores que se sobrepõem. Funcionários só recebem valores de bonificações publicadas e não veem justificativas administrativas. A exportação CSV específica do ciclo é restrita à administração e bloqueada antes da apuração; usa proteção contra fórmulas e não inclui autoria ou votos individuais. O CSV e os indicadores gerais de execução anteriores continuam numa área complementar acessível por Indicadores de execução.

Visual: cartões com ícones/cores e entrada em sequência, barras de pontuação com preenchimento, linhas da classificação com entrada breve e detalhes com transição de opacidade. Redução de movimento desativa animações. Tabelas contêm sua própria rolagem no celular. Auditoria continua fora do menu, mantendo os registros internos.

Implementação centralizada em `CycleReportService`, compartilhada por `CycleController` e `ReportController`; parciais de resumo/classificação/financeiro evitam discrepância entre telas. Não houve migration, alteração do banco local ou recálculo de resultados existentes. As expectativas dos testes antigos de indicadores foram direcionadas explicitamente à área complementar, preservando sua cobertura.

Validação: **226 testes PHP / 1.393 verificações**, **três testes JavaScript**, build e Chrome aprovados. Seis testes novos cobrem seleção do ciclo, título congelado, zero válido e votos insuficientes, contagem única, estados financeiros, privacidade, ranking/CSV bloqueados antes da apuração, filtro de ciclo, fórmula em CSV e ausência de ciclos. Chrome passou em `storage/app/qa-reports/2026-10-19T20-55-49.851Z/`, com relatórios nos dois temas, detalhes com âncora, movimento reduzido, 320/390 px e regressão até voto/apuração/decisão. Capturas de relatórios claro/escuro inspecionadas; ajustado o contraste do selo de situação no tema escuro. Guia novo: `docs/RELATORIO_POR_CICLO.md`.

## 19/10/2026 — modernização dos indicadores gerais de execução

A tela `/reports?view=general` foi reorganizada a pedido do usuário. O topo explica o recorte e mantém acesso ao relatório por ciclo. Filtros têm grade responsiva, limpeza explícita e indicação de atualização. Quatro cartões com ícones destacam propostas recebidas, resultados validados, tempo de execução e valor aprovado.

As barras nativas verdes foram substituídas por comparações com cantos arredondados e preenchimento animado. Distribuição apresenta quantidade e percentual; tempos são comparados à etapa mais demorada, sem sugerir percentual de conclusão. Durações curtas usam minutos ou horas, inclusive “Menos de 1 min”, e ausência de implementações concluídas mostra travessão em vez de um zero fictício. A evolução mensal mostra uma coluna e o valor quando há apenas um mês; o detalhamento numérico fica acessível em “Consultar valores por mês”.

Participação foi organizada em três números principais e tabela por ciclo. Indicadores validados e propostas têm cabeçalhos, estados vazios e tabelas com rolagem própria, acessível pelo teclado no celular. A exportação preserva os filtros e as regras anteriores de privacidade e resultados comprovados. Não houve alteração do banco, migration, permissão ou cálculo de votação/bonificação.

Animações discretas de entrada e preenchimento respeitam a preferência de reduzir movimento. A implementação usa Blade, CSS e Alpine já presentes, sem nova dependência. O roteiro automatizado de navegador passou a verificar esta tela nos dois temas, em 320/390 px, ausência de barras nativas, filtros, limpeza e estado vazio.

Validação concluída: **226 testes PHP / 1.393 verificações**, **três testes JavaScript**, build, Pint e Chrome aprovados. O navegador percorreu cadastro/revisão/voto/apuração/decisão e as novas verificações do relatório geral, sem regressões detectadas. Evidências: `storage/app/qa-reports/2026-10-19T21-10-00.111Z/`, incluindo `indicadores-gerais-light.png` e `indicadores-gerais-dark.png`. As capturas aguardam o fim das transições para registrar o estado final dos gráficos.

## 19/10/2026 — relatório administrativo de destinatários e bonificações

Implementado **Relatórios → Bonificações por ciclo**, inspirado no modelo enviado: cartões de resumo, filtros por rodada/período e tabelas de destinatários. Cada ciclo apurado ocupa um bloco, preservando todas as ideias e múltiplas bonificações/participantes. Há paginação de dez ciclos; os cartões somam todo o recorte filtrado, não somente a página.

O relatório mostra posição, título público congelado, pontuação, quantidade de votos válidos, pessoa destinatária, valor individual e situação da bonificação. A quantidade máxima configurada continua aplicada pelo fluxo existente de decisão; o relatório apenas exibe os registros. O destaque de maior quantidade de votos é separado da classificação por nota e inclui empates. Rodadas não apuradas ficam fora do relatório.

As divisões coletivas usam os valores gravados na proposta de bonificação, sem recalcular participantes atuais. Registros antigos sem divisão usam o destinatário original do reconhecimento. Os totais não multiplicam o prêmio pelo número de participantes; aprovados incluem divulgados, e rejeitados ficam fora dos totais aprovado/pendente. A página esclarece que aprovação/divulgação não comprovam pagamento bancário.

A rota `/reports/awards` exige `manage-admin` antes de buscar os dados, inclusive no acesso direto. Funcionários e avaliadores sem acesso administrativo não recebem nomes ou valores. Não foi alterada a divulgação pública, a votação, o banco local ou o procedimento de anonimização. Sem migration ou nova dependência.

Validação desta entrega: **230 testes PHP / 1.422 verificações**, três testes JavaScript, build, Pint e Chrome aprovados. Quatro testes novos cobrem acesso negado a funcionário/avaliador, divisões históricas e registros antigos, totais sem duplicação/rejeitados, separação das rodadas, empate em votos, pontuação diferente da quantidade de votos, filtros no fuso da empresa e exclusão de ciclos não apurados. O Chrome validou acesso direto negado, filtros/limpeza, 320/390 px e capturas claro/escuro inspecionadas em `storage/app/qa-reports/2026-10-19T21-14-09.225Z/`. No roteiro de navegador foi corrigida uma espera: o teste do teclado agora aguarda o foco entrar no menu móvel antes de enviar Tab, respeitando a atualização assíncrona do Alpine.
