# Conferência local — 18/10/2026

## Resultado

A versão foi verificada com PHP 8.5, SQLite, Node.js e Google Chrome headless. A suíte inicial tinha 146 testes PHP. Os novos testes cobrem revisão/restrição/exclusão de documentos, retenção com preservação obrigatória, falhas de armazenamento, elegibilidade própria por faixa, conta anonimizada, retorno da confirmação de senha e restauração real de uma cópia SQLite.

| Conferência | Resultado |
|---|---|
| Suíte PHP em SQLite | 211 testes aprovados, com 1.263 verificações |
| Formulário por etapas em JavaScript | 3 testes aprovados |
| Formatação PHP | Pint aprovado |
| Instalação reproduzível de frontend | `npm ci --ignore-scripts --no-audit --no-fund` concluído |
| Compilação de produção | `npm run build` concluído |
| Navegação em Chrome | `npm run test:browser` aprovado, com capturas e resultado JSON |
| Migration nova | Aplicada no banco local; sem migrations pendentes na conferência |
| Retenção de arquivos na base local | Simulação executada; nenhum arquivo excluído por essa simulação |
| Backup SQLite | Snapshot criado e restaurado em destino separado; integridade, contagens e documentos conferidos |
| MySQL | Não validado nesta sessão: acesso recusado ao usuário configurado para o banco de testes |
| SMTP em caixa real, HTTPS externo e hospedagem | Fora da validação local; serão tratados após a escolha dos serviços |

## Navegação conferida automaticamente

Nos temas claro e escuro: Painel, Administração, Pessoas, Organização, Cadastro de pessoa, Votações, Relatórios, Perfil, Solicitações de privacidade e Auditoria. Cartões de conteúdo, formulários e tabelas permanecem parados. Os indicadores do Painel/Administração têm hover de 5 px com mouse, desativado com redução de movimento. O hover da navegação usa fundo `#e8eef4` no tema claro e `#334155` no escuro, com texto legível.

No celular (390 × 844): validação de campos obrigatórios, avanço das três etapas de cadastro, revisão, confirmação de envio, abertura do menu e foco via teclado. Foi conferida a ausência de rolagem horizontal da página nesse percurso.

Na proposta fictícia: envio de TXT privado, confirmação de senha, retorno à proposta, restrição do documento e exclusão definitiva. Esses testes utilizam arquivos descartáveis e não apagam documentos da base de trabalho.

As execuções aprovadas do teste de navegador não registraram erros JavaScript nem respostas HTTP 500 nos percursos monitorados. As evidências ficam em `storage/app/qa-reports/`, separadas por execução, com `resultado.json` e capturas dos temas e do celular.

## Percurso adicional do processo completo

Em um servidor separado com banco e armazenamento temporários, também foram percorridas no Chrome as ações de triagem, avaliação técnica, aprovação, plano, implementação, medição, validação de resultados, conclusão e publicação revisada. Depois foram conferidos voto, revisão das respostas, confirmação, recibo, apuração, aprovação e divulgação do reconhecimento, além da renderização do gráfico de relatórios.

Os horários do ciclo nesse ambiente descartável foram adiantados diretamente na base de teste para não aguardar o período de votação. A suíte PHP testa as regras de prazo com relógio controlado. Esse ajuste não foi aplicado a ciclos da base local de trabalho.

## Backup e restauração efetivamente realizados

- Cópia criada: `storage/app/backups/20261018-170609-mXhJvhIj`.
- Destino restaurado: `storage/app/restore-check/delivery_20261018_test`.
- O arquivo SQLite original não foi substituído.
- Os testes adicionais verificam que alterações posteriores ao snapshot não alteram a cópia, que documentos são restaurados com conteúdo idêntico, que checksums inválidos são recusados e que um destino já existente não é sobrescrito.

## Limites para a avaliação humana

Use [ROTEIRO_QA.md](ROTEIRO_QA.md) para a avaliação exploratória. A conferência acima não certifica acessibilidade completa, compatibilidade com todos os navegadores, desempenho sob carga, entrega SMTP ou operação em hospedagem. Não houve publicação externa nesta entrega.

A retenção de textos corporativos, auditoria e backups depende da decisão da empresa. Anonimizar a conta e excluir um arquivo privado não equivalem a eliminar automaticamente todos os dados pessoais de todos os registros históricos. Os formulários exigem revisão e justificativa para as decisões administrativas.

## Complemento após QA: avisos, setores e ciclos no mesmo dia

Os cartões de avisos preenchidos foram conferidos no Chrome com fundo escuro e texto legível. A automação guarda também `avisos-escuro.png`.

Os setores foram migrados conforme a correspondência informada pelo usuário: Operações → Produto, Financeiro → Administração, Vendas → Sucesso do Cliente; Tecnologia permanece. Material Didático foi criado. Recursos Humanos foi inativado para preservar os registros existentes. A migration foi aplicada localmente após backup em `storage/app/backups/20261018-174958-bOPGDaJg`.

O teste de navegador agora usa servidor em UTC e horários de ciclo em Brasília, cria um ciclo curto, salva e confere que o formulário reexibe exatamente os horários digitados. A suíte PHP cobre criação, edição, votação e apuração no mesmo dia, inclusive quando a data UTC já é o dia seguinte à data em Brasília. Os horários previamente gravados não foram alterados. Evidências desta rodada: `storage/app/qa-reports/2026-10-18T17-49-58.407Z/`.

A identificação da implementação foi coberta por testes dos três estados na página de voto e no ciclo, independência em relação ao estado atual da proposta e compatibilidade com snapshots anteriores, inclusive nota de resultados igual a zero. Os ciclos de seleção acrescentados nesta entrega permitem propostas antes da execução; a exigência de resultados validados permanece no reconhecimento por resultados.


## Novo ciclo de seleção antes da implementação

Foram acrescentados 15 testes funcionais para registro sem estimativas/recursos, revisão de privacidade após triagem, votação sem implementação ou parecer técnico, impedimentos de autoria/coautoria, unicidade e critérios do ciclo, congelamento dos participantes, classificação sem aprovação automática, abstenções e desempate, votos mínimos, decisão justificada, limite de selecionadas, revisão de alertas, retorno ao banco e participação em outro ciclo.

A cobertura inclui bonificação opcional com divisão de R$ 101,01 em R$ 60,60/R$ 40,41, aprovação/divulgação sem finalizar a execução, justificativas administrativas protegidas, avaliação técnica posterior e o percurso selecionada → plano → execução → resultados → validação → publicação dos resultados → ciclo de reconhecimento → finalização. Um parecer técnico desfavorável posterior impede iniciar/retomar a execução até ser resolvido por nova avaliação.

A suíte completa passou com **193 testes e 1.088 verificações**. Os três testes JavaScript, Pint, compilação Vite e compilação de templates Blade também passaram. No Chrome, a execução `storage/app/qa-reports/2026-10-18T20-21-30.363Z/` aprovou as telas de ambos os tipos de ciclo nos dois temas e o percurso com contas distintas: registro rápido no celular, triagem, revisão, abertura, voto, apuração e decisão para executar. Não houve erro JavaScript ou HTTP 500 nos percursos monitorados.

As datas foram ajustadas somente na base descartável desse teste de navegador para simular o início e o fim sem aguardar dez minutos. A base de trabalho recebeu somente a nova migration de decisões, depois do backup `storage/app/backups/20261018-201750-MjgPTg1e`; ciclos existentes não foram convertidos. Não havia migrations pendentes após a aplicação.

O roteiro humano está em [CICLO_SELECAO.md](CICLO_SELECAO.md). MySQL, serviço de e-mail real e publicação externa continuam fora desta validação local.


## Listagem de ciclos e navegação lateral

A suíte PHP completa passou com **196 testes e 1.108 verificações** após separar a listagem (`/cycles`) da criação (`/cycles/create`). A cobertura verifica estado vazio, acesso ao formulário por administrador, bloqueio para funcionários, ocultação de rascunhos e preservação dos horários digitados quando há erro.

O teste de navegador foi ampliado para verificar que a listagem não contém formulários de cadastro, que Novo ciclo abre a página correta e que os ciclos salvos aparecem na tabela. Também confere menu fixo à esquerda no desktop, largura disponível para o conteúdo, temas claro/escuro, gaveta no celular, fechamento por Escape com retorno do foco, ciclo de Tab/Shift+Tab dentro do menu e ausência de rolagem horizontal da página em 320 e 390 px. Tabelas podem rolar internamente.

Os três testes JavaScript e Pint passaram. Templates Blade foram compilados e a compilação Vite foi concluída. Não foi necessária nova migration nesta alteração de navegação.

Evidências aprovadas desta alteração: `storage/app/qa-reports/2026-10-18T21-24-43.282Z/`, incluindo `listagem-ciclos.png` e `resultado.json`. Nenhum erro JavaScript ou HTTP 500 foi registrado nos percursos monitorados.


## Continuidade autônoma: disponibilidade e teclado

A revisão corrigiu o foco quando a janela alterna entre 1023/1024 px e tornou também a barra superior indisponível enquanto o menu está aberto no celular. A escolha de propostas passou a desabilitar e explicar ideias que participam de outro ciclo ou aguardam decisão anterior, usando a mesma regra da confirmação no servidor.

A suíte completa passou novamente com 196 testes e 1.116 verificações. O Chrome aprovou os novos percursos de bloqueio/liberação de propostas, erro de período com campos preservados, fechamento pelo fundo e foco ao redimensionar. Evidências: `storage/app/qa-reports/2026-10-18T21-29-47.934Z/`. Não houve erro JavaScript ou HTTP 500 nos percursos monitorados. O relatório detalhado desta continuidade está em [STATUS.md](../STATUS.md).


## Administração: setores, acessos e critérios em áreas próprias

A suíte completa passou com 202 testes e 1.173 verificações. Os novos testes cobrem separação das telas, filtros ativos/inativos, vinculação/transferência/remoção de setor, preservação de permissões e ideias históricas, auditoria, bloqueio de novos vínculos em setor inativo e autorização das novas rotas. Os testes anteriores continuam cobrindo a preservação dos pareceres após editar ou desativar critérios.

O Chrome aprovou as telas nos dois temas e os percursos de criação de setor, vínculo/remoção, inativação, criação/edição/inativação de critério e retorno às respectivas listagens. Também aprovou a largura da página em 320/390 px nas áreas administrativas. Evidências: `storage/app/qa-reports/2026-10-18T21-44-06.111Z/`. Nenhum erro JavaScript ou HTTP 500 foi registrado nos percursos monitorados. Pint, build Vite e templates Blade passaram; a alteração não exige migration. Os dados do QA permanecem isolados da base local de trabalho.


## Visual e animações inspirados na referência

A adaptação usa CSS, sem novas dependências. Indicadores do Painel/Administração compartilham um componente e apresentam entrada por opacidade e hover de 5 px somente com mouse. A gaveta desliza e as linhas SVG dos gráficos são desenhadas na entrada. Reduzir movimento desativa esses efeitos e mantém o gráfico completo; dispositivos de toque não recebem o deslocamento por hover. Os valores e as regras de acesso foram preservados.

Foram aprovados novamente 202 testes PHP (1.173 verificações), 3 testes JavaScript, Pint, compilação Vite, templates Blade e verificação de espaços do Git. No Chrome, os percursos anteriores e as novas verificações de hover, retorno, redução de movimento, gráfico e toque passaram nos temas claro/escuro. O Painel e as demais telas conferidas não apresentaram rolagem horizontal da página em 320/390 px. Evidências: `storage/app/qa-reports/2026-10-18T23-46-58.353Z/`. Nenhum erro JavaScript ou HTTP 500 foi registrado nos percursos monitorados. O roteiro humano está na seção 19 de `ROTEIRO_QA.md`.


## Primeira rodada: sem exigência de e-mail

A suíte passou com 211 testes PHP e 1.263 verificações, incluindo nove cenários de acesso manual. Os testes legados mantêm `EMAIL_VERIFICATION_REQUIRED=true` e `ACCOUNT_INVITATION_DELIVERY=email` nos arquivos PHPUnit para preservar a cobertura da opção estrita. `ManualAccessTest` ativa explicitamente a configuração da rodada (`false`/`manual`), cobrindo permissões sem e-mail verificado, cadastro pendente, confirmação de senha administrativa, justificativa, token privado/uso único/renovação/expiração, recuperação com revogação de sessões, impedimento de contas bloqueadas, proteção do último administrador e SMTP condicional.

`npm test`: três testes aprovados. `vendor/bin/pint --test --dirty` e `git diff --check`: aprovados. `npm run test:browser` compila os assets e usa banco/storage descartáveis, com configuração manual explícita. A execução aprovada está em `storage/app/qa-reports/2026-10-19T00-47-41.571Z/resultado.json` (a pasta usa UTC; a sessão é de 18/10 em Brasília). Inclui cadastro administrativo, confirmação de senha, geração de link, definição da senha em outro contexto do navegador, login sem confirmação de e-mail e bloqueio de acesso administrativo ao funcionário. Nenhum link individual foi incluído em captura ou relatório.

A primeira tentativa do navegador parou no cenário antigo de documento porque o cenário novo já havia confirmado a senha na mesma sessão. O script passou a iniciar nova sessão administrativa entre esses percursos, e a execução completa seguinte passou. Não houve falha funcional de confirmação de senha.

A configuração local efetiva foi conferida como confirmação de e-mail desativada e entrega manual. Não houve migration nova nesta etapa, alteração do `.env`, envio de mensagem real ou publicação. MySQL, TLS público, restauração no futuro servidor e capacidade com funcionários reais continuam sem validação externa.
