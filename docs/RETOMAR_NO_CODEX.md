# Texto para retomar em outro chat

Atualização de 22/10: Regras financeiras reúne modelos de perguntas/pesos e regras de bonificação. A criação de ciclo de seleção exige os dois seletores; não restaure o bloco antigo com valor e regras avulsos. O usuário escolheu o modelo com várias perguntas. Os cadastros são copiados ao criar o rascunho, sem mudar ciclos existentes quando editados/inativados. Critérios individuais de parecer continuam acessíveis em seção própria. Leia a atualização mais recente de STATUS.md e docs/CICLO_SELECAO.md.


Copie o texto abaixo depois de abrir a pasta deste repositório no Codex:

> Continue o desenvolvimento deste projeto Laravel na branch de trabalho atual (confira o Git antes de alterar). Primeiro leia `AGENTS.md`, `STATUS.md`, `docs/ENTREGA_VOTACAO_SEXTA.md`, `docs/CONTINUAR_EM_OUTRO_PC.md`, `docs/CICLO_SELECAO.md`, `docs/VALIDACAO_LOCAL.md` e `docs/ROTEIRO_QA.md`. Confira o estado real do Git e do ambiente antes de alterar arquivos; preserve meu trabalho existente e não exponha o `.env` ou credenciais.
>
> O prazo foi adiado para novembro de 2026, sem dia específico. A prioridade é concluir as pendências reais registradas na atualização de 21/10/2026 de STATUS.md. A premiação desta rodada é pela ideia selecionada antes da implementação; valor e limite de ideias premiadas são configuráveis. A alternativa 1 foi aprovada: o fluxo é registrar, revisar em uma ação (Liberar para votação, Pedir ajuste ou Arquivar), votar, apurar, decidir e reconhecer; depois acompanhar a implementação. Autor e coautores não votam na própria proposta. Hikaro e Thalita são possíveis responsáveis pelas decisões, mas as contas reais e perfis ainda precisam ser definidos.
>
> Nesta versão não exigimos confirmação por e-mail: `EMAIL_VERIFICATION_REQUIRED=false` e `ACCOUNT_INVITATION_DELIVERY=manual`. O administrador cadastra a pessoa e usa Redefinir senha → Nova senha/Confirmar senha → Concluir, inclusive no primeiro acesso. O fluxo manual não depende de link. Não marque e-mails artificialmente como verificados. Preserve permissões, bloqueios, proteção do último administrador e auditoria.
>
> Já foram implementados navegação lateral, listagem de ciclos separada da criação, áreas administrativas separadas, setores e vínculos, critérios, correções de temas/hover e animações discretas com redução de movimento. Não refaça essas partes sem identificar um problema concreto.
>
> Consulte os números atuais de validação em STATUS.md: SQLite, MySQL dedicado, JavaScript e Chrome foram conferidos. Carga real, HTTPS e restauração no futuro servidor ainda não foram homologados. Não trate os testes locais como publicação concluída. Retenção/anonimização, incluindo banco de ideias, ainda exige revisão e política corporativa.
>
> A empresa não dispõe de servidor. Primeiro avaliamos opções gratuitas; depois pedi alternativas baratas. Foram comparadas Hostinger KVM 1 (preço promocional por período), DigitalOcean Basic 2 GB (US$ 12/mês, backup à parte) e Railway (cobrança por consumo). Nenhuma hospedagem foi escolhida ou contratada. Confira novamente preços e condições antes de recomendar ou preparar uma contratação. Não publique nem contrate serviços sem uma decisão explícita minha.
>
> Caso os dados atuais não estejam nesta máquina, não suponha que o Git os trouxe. Configure um ambiente local separado ou conduza uma restauração privada conforme os comandos existentes. Não execute `migrate:fresh` nem seeders sobre dados a preservar. Explique cada parte concluída e atualize `STATUS.md` para meu QA. Use o formato de julgamento da ideia somente quando eu disser “veredito”.


Atualização: a alternativa 1 de relatórios foi aprovada e implementada. Leia também `docs/RELATORIO_POR_CICLO.md`. Relatórios abre por ciclo, classificação/decisão/bonificação ficam separadas, com CSV por ciclo; indicadores antigos continuam em área complementar. A rota de auditoria permanece restrita, mas o menu Auditoria foi retirado a pedido do usuário.


Atualização de 22/10/2026: a migração de Tailwind 3 para 4 foi autorizada explicitamente e concluída. O projeto usa Tailwind/Vite 4.3.3 com configuração em `resources/css/app.css`; os antigos `tailwind.config.js`/`postcss.config.js` foram removidos. Não reinstale Tailwind 3 seguindo instruções geradas antes dessa autorização. QA-08 foi resolvido e a auditoria npm completa não apontou vulnerabilidades. Consulte `STATUS.md` para as evidências finais.
