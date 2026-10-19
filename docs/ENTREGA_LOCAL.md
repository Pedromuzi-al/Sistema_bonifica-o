# Entrega local — 18/10/2026

## Escopo consolidado

A versão já continha cadastro por etapas, triagem, critérios e pareceres, implementação e medição, publicação revisada, votação, apuração, reconhecimento, perfis/permissões, organização, coautores, documentos privados, solicitações de privacidade e preparação de operação. A revisão desta entrega partiu de 146 testes PHP passando; essas funcionalidades foram preservadas e verificadas.

## Alterações desta entrega

| Parte | O que mudou | Como funciona |
|---|---|---|
| Hovers | Movimento restrito a indicadores, botões discretos e cores coerentes com cada tema | Indicadores do Painel/Administração sobem 5 px com mouse; conteúdo e formulários ficam parados. O movimento é desativado com Reduzir movimento e não é aplicado ao toque |
| Navegação e acessibilidade | Página atual identificada, logo mais adequado ao celular, foco de teclado e redução de movimento | `aria-current`, foco visível, largura responsiva e `prefers-reduced-motion` |
| Documentos | Revisão, restrição, retenção individual e exclusão definitiva do arquivo | Nova migration guarda decisão/revisor/prazo/preservação; decisões exigem administrador com auditoria, confirmação de senha e justificativa |
| Rotina de retenção | Simulação e exclusão de documentos vencidos já revisados | `app:purge-private-documents`, com `--execute` para gravar; o scheduler executa diariamente; preservação obrigatória impede exclusão |
| Falha na exclusão | Download bloqueado e possibilidade de repetir a remoção | A decisão de excluir é gravada antes de remover o arquivo; falhas deixam o estado pendente, e a auditoria registra a conclusão |
| Faixas de prêmio | Requisitos próprios por categoria | Mínimos opcionais de votos, nota técnica, resultados e categoria da publicação, congelados na abertura e aplicados na apuração |
| Relatórios | Gráfico mensal, participação por ciclo e benefícios separados por setor | Gráfico SVG com descrição acessível; contagens agregadas; agrupamento exige mesmo setor, indicador, unidade e período |
| CSV | Setor de origem e início/fim da medição | Exportação mantém proteção contra fórmulas e evita dados de autoria/votos individuais |
| Privacidade | Conta anonimizada protegida contra reedição e seção de perfil sem duplicação | Validação no serviço e bloqueio de edição/reativação na interface; confirmação de senha retorna ao contexto correto |
| Concorrência | Mesma ordem de bloqueio na análise de privacidade e administração | Removido o bloqueio de todas as contas na decisão de privacidade; utiliza o registro de coordenação da administração |
| Backup local | SQLite com documentos e restauração verificável | Snapshot SQLite consistente, manifesto com hashes/contagens, restauração em pasta separada e conferência dos arquivos restaurados |
| Testes | Cobertura adicional de documentos, elegibilidade, privacidade e restauração | Testes funcionais e comando de navegador reproduzível, sempre com base/arquivos isolados |
| Ambiente local | Anexos habilitados e migration aplicada | Opção apenas no `.env` local; os modelos/exemplos de produção continuam permitindo habilitação explícita |

## Validação e limites

O roteiro completo está em [ROTEIRO_QA.md](ROTEIRO_QA.md). A conferência automatizada atual fica registrada em [VALIDACAO_LOCAL.md](VALIDACAO_LOCAL.md).

A prioridade atual passou a ser a rodada de votação e bonificação até 23/10/2026, com acesso pela internet. Hospedagem gratuita ainda está em análise; a primeira versão dispensa confirmação e envio de e-mail. Não houve deploy nem envio de e-mails para funcionários reais. A entrega SMTP em caixa real e a operação externa com HTTPS não foram verificadas nesta sessão.

A política corporativa continua necessária para decidir prazos, preservação de registros de negócio e tratamento de dados pessoais em texto livre, auditoria e backups. Exclusão de documentos e anonimização de conta são operações distintas. Não há pagamento automático.

## Ajustes após o primeiro QA

- Avisos do painel: fundo explícito por tema, corrigindo o cartão claro com texto claro no modo escuro.
- Setores: Operações → Produto; Financeiro → Administração; Vendas → Sucesso do Cliente; Tecnologia mantida; Material Didático criado. Os vínculos foram preservados/migrados; Recursos Humanos permanece inativo para não apagar histórico.
- Ciclos curtos: criação e encerramento no mesmo dia já são permitidos. O erro observado vinha da interpretação dos horários em UTC. O formulário agora informa o fuso de Brasília e converte os valores para o fuso interno antes de validar e salvar, preservando os horários originais em erros de preenchimento.
- Criação e edição de rascunhos usam a mesma regra de conversão. Listagem, detalhe e avisos de votação exibem o fuso do programa.

## Situação da implementação na votação

A página de voto e os cartões do ciclo exibem uma indicação simples: Ainda será implementada, Em implementação ou Implementada. A situação é registrada no snapshot da abertura, junto aos textos revisados, para que o conteúdo avaliado não mude no meio da votação. Ciclos anteriores continuam compatíveis sem migration. Os títulos dos textos foram ajustados para descrever problema, solução e benefícios sem presumir execução concluída.

O novo ciclo de seleção descrito abaixo libera a votação antes da implementação. A exigência de resultados validados permanece somente no tipo de ciclo dedicado ao reconhecimento por resultados comprovados.

## Ciclo para selecionar antes de implementar

O padrão da tela Votações passou a ser Criar ciclo de seleção de ideias. Após triagem e revisão de privacidade, a proposta pode receber votos sobre utilidade, relevância e benefício esperado, mesmo sem implementação, indicadores ou parecer técnico completo. A apuração classifica; a equipe decide quais avançam e justifica viabilidade e recursos. Ideias iniciais não selecionadas permanecem no banco e podem participar de outro ciclo.

Votação e execução são independentes: abrir/apurar não muda a etapa operacional da proposta. A seleção de uma proposta inicial a aprova para planejar e executar; plano, responsável e prazo continuam obrigatórios para começar a implementação. A composição dos participantes fica preservada após a primeira abertura.

A bonificação pela ideia selecionada é opcional, inicialmente zero. Quando configurada, surge após a decisão, com divisão entre participantes e aprovação/divulgação separadas, sem encerrar a execução. O tipo Reconhecimento por resultados comprovados mantém as regras anteriores e pode ser utilizado depois da medição e validação. A avaliação técnica posterior à seleção e a revisão específica dos resultados permitem essa continuidade, sem tratar estimativas como comprovação.

A migration `2026_10_18_200000_add_cycle_idea_decisions.php` acrescenta as decisões de seleção, sem converter ciclos anteriores. Foi aplicada na base local depois do backup `storage/app/backups/20261018-201750-MjgPTg1e`. O passo a passo e as regras estão em [CICLO_SELECAO.md](CICLO_SELECAO.md).


## Navegação lateral e listagem de ciclos

Votações agora abre exclusivamente a listagem com nome, tipo, período, situação e acesso ao ciclo. Quando vazia, apresenta uma mensagem. Administradores usam Novo ciclo para abrir uma página própria de cadastro (`/cycles/create`), preservando os dois tipos e a validação dos formulários. Funcionários continuam sem acesso à criação e sem acesso aos rascunhos.

O menu principal passou para a lateral esquerda no desktop, com a página atual identificada e atalhos de acordo com as permissões. O topo concentra o tema e a conta. No celular, a lateral abre por botão, mantém o foco dentro do menu, bloqueia interação com o conteúdo de fundo e fecha por Escape, botão ou fundo escurecido, devolvendo o foco ao acionador. Há um atalho de teclado para pular ao conteúdo. A alteração não exige migration.


## Administração separada por áreas

O painel administrativo reúne cartões de Setores, Pessoas e acessos, Critérios técnicos e Equipes. Setores passou a ter uma tabela própria com busca e filtro de ativos/inativos, criação separada e detalhe com pessoas vinculadas ou não vinculadas. É possível transferir/remover o setor de referência de uma conta com confirmação e auditoria, preservando permissões, equipes e setores das ideias anteriores. Setores inativos bloqueiam novos vínculos; contas desativadas ficam disponíveis apenas para consulta histórica. Não foi acrescentado campo de protocolos.

Pessoas e acessos passou a exibir uma tabela com contas, setor/equipes, perfil, permissões efetivas e situação, mantendo o formulário existente para editar acesso. Critérios técnicos possui listagem com filtros e botão Novo critério técnico, além de formulários próprios de criação/edição. Os serviços anteriores de auditoria e preservação dos pareceres foram mantidos. As áreas também aparecem como atalhos dentro de Administração no menu lateral. Não foi necessária migration.


## Adaptação visual inspirada na referência V14

A área autenticada ganhou superfícies mais leves, sombras discretas, bordas e cantos consistentes, botões compactos e seleção do menu em azul. O Painel recebeu uma apresentação com a identidade Conted Tech e indicadores com ícones e cores por situação, usando um componente compartilhado com a Administração. Os dados e regras de acesso foram preservados.

As animações usam CSS: entrada por opacidade dos indicadores em sequência, hover de 5 px somente nos indicadores em dispositivos com mouse, transição da gaveta e desenho das linhas dos gráficos. A preferência de reduzir movimento desativa esses efeitos. Não foram adicionados React, Framer Motion ou novas dependências; não é uma reprodução exata de um vídeo cujos movimentos não foram identificados. O código parcial fornecido orientou o hover, e os demais efeitos foram adaptados ao sistema.


## Acesso da primeira rodada

A confirmação de e-mail é opcional por configuração e vem desativada para esta rodada. A administração cadastra a pessoa, confirma a própria senha e gera um link individual, temporário e de uso único para o titular definir sua senha. A recuperação usa o mesmo procedimento. O registro de e-mail verificado não é preenchido artificialmente; permissões e bloqueios continuam aplicados. Consulte [ENTREGA_VOTACAO_SEXTA.md](ENTREGA_VOTACAO_SEXTA.md) para configuração, passos e pendências de publicação.


## Exclusão em lote das ideias

Em Ideias → Excluir ideias, administradores selecionam cadastros da página atual, avançam para confirmação, informam motivo e confirmam. A retirada das listagens utiliza `deleted_at`; histórico, auditoria e documentos são preservados. Ciclos/votos/bonificações, execução/resultados e preservação obrigatória impedem a exclusão; o lote é validado inteiro antes de gravar. A operação não apaga definitivamente os dados. Requer a migration `2026_10_19_170000_add_soft_deletes_to_ideas.php`.
