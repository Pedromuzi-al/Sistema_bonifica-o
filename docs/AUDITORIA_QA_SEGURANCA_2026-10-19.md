# Auditoria local de QA e segurança — 19/10/2026

Foram encontrados sete problemas reproduzidos em testes isolados e um conjunto de alertas de dependências. A auditoria não alterou regras da aplicação nem corrigiu os achados. As modificações que já existiam no workspace foram preservadas.

## Verificação executada

| Verificação | Resultado |
| --- | --- |
| `php artisan test --compact` | 226 testes, 1.393 asserções, todos passaram |
| `npm test` | 3 testes passaram |
| `npm run test:browser` | Build e 20 registros de cenários concluídos, sem erros registrados |
| `vendor/bin/phpunit docs/qa/AuditFindingsTest.php` | 7 reproduções, 48 asserções, todas confirmadas |
| `composer audit --format=json` | Nenhum advisory ou pacote abandonado reportado |
| `npm audit --json` | 7 pacotes afetados: 5 classificados como high e 2 moderate |
| `npm audit --omit=dev --json` | Nenhum alerta reportado |

Os sete testes de reprodução passam quando observam o problema atual. Não são testes de aceitação de segurança: após uma correção, suas expectativas devem ser invertidas. Permanecem fora de `tests/` para não incorporar comportamentos inseguros à suíte habitual.

PHPUnit usou SQLite em memória; o teste existente de concorrência usou banco temporário. Playwright criou aplicação, banco, contas e armazenamento descartáveis. Nenhum teste foi direcionado ao banco operacional. O build atualizou os artefatos compilados locais. Nenhum valor do `.env` foi incluído no relatório.

Evidências de navegador: `storage/app/qa-reports/2026-10-19T21-00-05.381Z/resultado.json` e capturas na mesma pasta.

## Achados por prioridade

### QA-01 — Alta: troca de senha mantém sessões e token de acesso persistente

Local: `app/Http/Controllers/Auth/PasswordController.php:23` e `app/Http/Middleware/EnsureAccountIsActive.php`.

`PUT /password` altera somente o hash. Não incrementa `access_version`, não remove outras sessões e não troca `remember_token`. O middleware usa a versão de acesso para revogação, sem conferir o hash da senha da sessão. Uma sessão já obtida por terceiro pode continuar válida mesmo depois de o titular trocar a senha.

Reprodução: criar usuário e registro representando outro dispositivo em `sessions`; alterar a senha com a senha atual correta; verificar que o registro, a versão e o token permanecem iguais. Uma requisição com a versão antiga continua acessando `/dashboard`. A reprodução verifica estado e passagem pelo middleware; não simula um cookie roubado entre dois navegadores.

Correção sugerida: executar a troca em transação, incrementar a versão, rotacionar o token persistente e revogar sessões anteriores. Atualizar a versão da sessão atual somente se a política permitir mantê-la conectada. Revalidar concorrência com recuperação de senha.

### QA-02 — Alta: e-mail da conta pode ser trocado sem reconfirmação

Locais: `app/Http/Requests/ProfileUpdateRequest.php:29`, `app/Http/Controllers/ProfileController.php:52` e `app/Services/AdministrationService.php:49`.

O titular pode enviar `PATCH /profile` apenas com `name` e `email`. A alteração não exige senha atual ou confirmação recente. O controlador atualiza a versão da sessão que fez a alteração; com verificação de e-mail desligada, essa sessão continua acessando o sistema.

Reprodução confirmada: usuário autenticado, `innovation.email_verification_required=false`, troca para outro e-mail sem campo de senha; alteração persistida e `/dashboard` retorna 200.

Impacto: quem já tiver acesso a uma sessão pode mudar a identidade de contato. Se a recuperação por e-mail estiver habilitada, pode usar o novo endereço para solicitar recuperação. Esse encadeamento depende dessa configuração; a auditoria confirmou a troca sem senha, sem enviar mensagens reais. O padrão do arquivo de configuração desliga a verificação, mas isso não comprova a configuração de uma implantação.

Correção sugerida: exigir confirmação recente de senha para mudança do próprio e-mail e validar o novo endereço antes de torná-lo o endereço principal. Preservar o caminho administrativo com autorização e trilha próprias.

### QA-03 — Média: confirmação de senha sem limitação de tentativas

Locais: `routes/auth.php`, rota `POST confirm-password`, e `app/Http/Controllers/Auth/ConfirmablePasswordController.php:40`.

O login tem limitador, mas a reconfirmação usa `Auth::guard('web')->validate()` diretamente, sem limitador no controlador ou na rota. Foram feitas 20 tentativas erradas consecutivas na mesma sessão; todas chegaram à validação e retornaram 422. A tentativa correta seguinte foi aceita.

Impacto: uma sessão comprometida permite testar senhas contra a barreira que protege exportações privadas, revisão de documentos e geração de links de acesso. Não é uma forma de autenticação anônima.

Correção sugerida: limitar tentativas por conta e IP, com espera progressiva e resposta 429. Avaliar também os outros endpoints que conferem senha atual.

### QA-04 — Média: autocadastro deixa usuário sem acesso às funções principais

Local: `app/Http/Controllers/Auth/RegisteredUserController.php:42`.

A criação pública salva e autentica o usuário, mas não atribui papel nem permissões. Mesmo depois de verificar o e-mail, o novo usuário não consegue criar ideias nem consultar ciclos. O teste existente de cadastro verifica criação/login, sem conferir o uso posterior.

Reprodução: habilitar cadastro, executar o seeder de permissões, cadastrar pessoa e marcar seu e-mail como verificado. A conta fica sem papéis; `GET /ideas/create` com Accept JSON retorna 403, assim como `/cycles`.

Correção sugerida: decidir se o cadastro é imediato ou depende de aprovação. No primeiro caso, atribuir o papel mínimo adequado em transação; no segundo, mostrar estado pendente e fornecer fluxo explícito de aprovação. Não conceder permissões administrativas.

### QA-05 — Média: entrada numérica extrema provoca erro 500 ao registrar resultados

Locais: `app/Http/Requests/ResultRequest.php:31` e `app/Services/IdeaWorkflowService.php:140`.

As medições usam a regra `numeric`, sem limite de magnitude ou verificação de finitude. A string `1e309` passa pela validação; sua conversão para float produz infinito e o cálculo não pode ser serializado como JSON.

Reprodução: avaliador autorizado, ideia em implementação, `POST /ideas/{id}/results` com medição válida e `before="1e309"`, `after="1"`. Resultado confirmado: HTTP 500 e nenhum resultado salvo. Requer permissão de avaliação; não demonstra indisponibilidade global do serviço.

Correção sugerida: definir limites de negócio para os valores, rejeitar números não finitos e validar também os resultados de operações aritméticas. Responder 422 para valores fora da faixa.

### QA-06 — Média: retrabalho reaproveita validação antiga

Local: `app/Services/IdeaWorkflowService.php:72`.

Ao retornar de `validation` para `implementation`, o sistema mantém `validated_at`, o validador e o estado de conclusão do plano. É possível seguir novamente para `validation` e depois `completed` sem registrar ou validar resultados após o retrabalho.

Reprodução: ideia em validação, plano concluído e resultado validado três dias antes. Enviar transições `implementation → validation → completed`. Todas são aceitas; a data antiga permanece e nenhum evento `results_validated` novo é criado.

Impacto: o fluxo pode apresentar uma implementação revisada como concluída usando evidências anteriores. A gravidade depende da regra de negócio sobre retrabalho, mas hoje não existe distinção entre validação anterior e validação da execução retomada.

Correção sugerida: invalidar a validação ao reabrir implementação, reabrir o plano e exigir nova validação antes de concluir, preservando os registros anteriores na auditoria.

### QA-07 — Baixa: recuperação por e-mail revela existência de contas

Local: `app/Http/Controllers/Auth/PasswordResetLinkController.php:43`.

Com `innovation.invitation_delivery=email`, endereço existente recebe status de sucesso; endereço inexistente recebe erro no campo e-mail. A distinção permite enumerar contas. No modo manual, o controlador retorna antes dessa consulta.

Reprodução: transporte de e-mail em memória; solicitar recuperação para conta cadastrada e para endereço inexistente; comparar status e erros da sessão. Nenhuma mensagem real foi enviada.

Correção sugerida: retornar a mesma mensagem pública para os dois casos e aplicar limitação global/per IP à solicitação, mantendo detalhes internos apenas nos logs adequados.

### QA-08 — Média no contexto local: dependências de desenvolvimento com advisories

O `npm audit` reportou sete pacotes afetados na árvore do Tailwind 3.4.19. Isso não significa sete vulnerabilidades independentes: há dois advisories de origem propagados pelos dependentes.

- `braces@3.0.3`: esgotamento de pilha por padrões profundamente aninhados; [GHSA-vfj7-8cjw-p6xm](https://github.com/advisories/GHSA-vfj7-8cjw-p6xm). Propagação por `chokidar`, `micromatch`, `fast-glob` e `tailwindcss`.
- `postcss-selector-parser@6.1.4`: consumo excessivo de CPU ao analisar seletores; [GHSA-rj75-hqrm-r3gf](https://github.com/advisories/GHSA-rj75-hqrm-r3gf). Propagação por `postcss-nested` e `tailwindcss`.

O audit classificou cinco entradas como high e duas como moderate. Neste projeto estão nas ferramentas de desenvolvimento/build; `npm audit --omit=dev` retornou zero. Não foi demonstrado caminho HTTP da aplicação até os parsers afetados.

Correção sugerida: atualizar a cadeia de build de forma compatível e repetir build, wizard e navegador. Há Tailwind 3 e 4 instalados na árvore; não aplicar `npm audit fix --force` sem revisar a compatibilidade do CSS.

## Cobertura e limites

Foram revisados rotas, autenticação, perfis, permissões, políticas de ideias, transições, votação, seleção, prêmios, documentos privados, privacidade, relatórios, cabeçalhos, seeding e rotinas locais de backup. A suíte existente cobre também administração, bloqueio/reativação, exclusão em lote, concorrência de voto, relatórios e governança. A aprovação dos testes indica somente os cenários exercitados.

No navegador foram exercitados painel, administração, primeiro acesso manual, setores, critérios, envio/revisão de ideia, documento privado, ciclos, voto, decisão, relatório, exclusão e navegação móvel/teclado. Não houve erro de página ou HTTP 500 nos fluxos registrados por esse script. O erro numérico foi encontrado nos testes adicionais.

Não foram realizados ataques em produção, teste de carga, varredura de rede, validação de servidor/TLS/DNS/SMTP nem execução da suíte no MySQL. A configuração e exposição reais de uma implantação não foram certificadas. O resultado não garante ausência de outras falhas.

Prioridade sugerida: revogação após troca de senha e proteção da alteração de e-mail; limitação da reconfirmação; integridade de retrabalho e limites numéricos; autocadastro; dependências e enumeração.
