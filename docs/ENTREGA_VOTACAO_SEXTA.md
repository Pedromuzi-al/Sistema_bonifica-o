# Rodada de ideias até sexta-feira, 23/10/2026

## Escopo acordado

A rodada premia a ideia selecionada, mesmo antes da implementação. Na criação do ciclo de seleção, a administração configura o valor por ideia e o limite de ideias selecionadas. O valor zero mantém a seleção sem prêmio financeiro. A apuração ordena os votos; a decisão humana de selecionar exige justificativa. Aprovar e divulgar o reconhecimento são ações separadas. O sistema registra a bonificação e sua divisão; não realiza transferência bancária.

Hikaro e Thalita foram indicados como possíveis responsáveis pelas decisões. As contas reais, os perfis e a substituição em caso de impedimento ainda precisam ser definidos. Não foram criadas contas usando apenas esses nomes. Autor e coautores não podem votar/avaliar a própria contribuição; mantenha uma pessoa autorizada sem participação para decisões que exigem independência.

## Primeiro acesso sem envio de e-mail

Configuração desta versão:

```dotenv
EMAIL_VERIFICATION_REQUIRED=false
ACCOUNT_INVITATION_DELIVERY=manual
REGISTRATION_ENABLED=false
```

Depois de alterar configuração no ambiente, execute `php artisan config:clear`; em produção, gere novamente o cache de configuração. O `.env` local não foi sobrescrito. A confirmação de e-mail permanece disponível como opção futura. Dispensar confirmação não preenche `email_verified_at`: o endereço continua sendo o identificador de login, sem alegação de ter sido verificado.

1. Prepare o administrador inicial com `php artisan app:create-administrator EMAIL_REAL --name="NOME_REAL"`. O comando pede a senha sem mostrá-la. Não use o seeder de demonstração em produção.
2. Entre em Administração → Pessoas e acessos → Cadastrar pessoa. Informe nome, e-mail de identificação, perfil e setor/equipes quando aplicável. A senha inicial é aleatória e não é compartilhada.
3. Abra Gerenciar acesso. Confirme sua própria senha e informe o motivo para gerar o link de primeiro acesso.
4. Confira a identidade e entregue o link exclusivamente ao titular por um canal individual. Ele aparece somente na resposta da geração; não o copie para grupos, capturas ou documentos de suporte.
5. O titular abre o link, define sua senha e entra no sistema. Enquanto isso, a conta continua com primeiro acesso pendente.
6. Para recuperar uma conta, siga o mesmo procedimento em Gerenciar acesso → Recuperação de acesso. Não há envio automático na tela Esqueci minha senha.

O prazo do link segue `auth.passwords.users.expire` (60 minutos por padrão). É de uso único; gerar outro invalida o anterior. Ao definir nova senha, sessões anteriores são revogadas. O registro de auditoria contém autor, titular, finalidade e motivo, sem o token. Contas bloqueadas/desativadas não recebem link enquanto não forem reativadas. O administrador confirma a própria senha e a geração tem limite de requisições.

## Sequência operacional

1. Antes de divulgar: escolher o ambiente público e validar HTTPS, persistência, configuração, backup e restauração nesse ambiente. Criar contas reais; desabilitar cadastro público e retirar contas de demonstração. Executar `php artisan app:production-check` e resolver as pendências.
2. Cadastrar propostas e fazer triagem/revisão dos textos que serão divulgados. Não é necessário resultado comprovado para ciclo de seleção.
3. Em Votações → Novo ciclo, escolher seleção de ideias, período, valor e limite. Selecionar as ideias elegíveis e confirmar a abertura antes do horário inicial.
4. Funcionários votam no período. O cartão informa a situação de implementação. Participantes da proposta têm impedimento de voto.
5. Após o fim, apurar; conferir o ranking; decidir quais ideias avançam com justificativa e dentro do limite. As não selecionadas ficam no banco para uma futura rodada.
6. Conferir valores e divisão entre autor/coautores; aprovar e divulgar os reconhecimentos. Registrar o pagamento na operação financeira da empresa, fora do sistema.
7. Guardar exportações e backup do fechamento; acompanhar as ideias escolhidas com responsável e prazo.

Execute também o roteiro de QA com pessoas reais antes da rodada oficial. Permanecem pendentes: teste MySQL, validação externa e política corporativa de retenção/anonimização, incluindo propostas guardadas no banco de ideias. A suíte local não comprova disponibilidade da internet ou capacidade para o número real de funcionários.

## Hospedagem sem mensalidade: pesquisa de alternativas

Nenhuma conta externa foi criada e não houve publicação. A compatibilidade abaixo é uma avaliação técnica; ainda precisa ser confirmada no ambiente escolhido.

| Caminho | O que oferece / limite decisivo | Adequação para esta semana |
|---|---|---|
| Servidor e subdomínio já existentes na empresa | Depende de haver espaço e permissão da TI. Pode manter Laravel, MySQL e arquivos privados, sem contratar um novo serviço. | Descartado pelo usuário: não há servidor disponível na empresa. |
| Oracle Cloud Always Free | Recursos gratuitos dentro dos limites elegíveis; inscrição geralmente exige cartão para verificação. Pode faltar capacidade na região e instâncias ociosas podem ser retomadas. | Candidato para servidor próprio com Laravel/MySQL, condicionado a cadastro e VM disponível. Exige administração, backup externo e HTTPS; não é garantia de entrega até sexta. |
| Render Free + banco persistente externo | O serviço gratuito dorme após 15 minutos e perde arquivos locais ao reiniciar. Render orienta não usar a modalidade gratuita em produção. Seu Postgres gratuito expira em 30 dias. | Avaliar apenas para demonstração/piloto. Não colocar SQLite nem anexos privados no disco local. |
| Neon Free como banco externo | Postgres sem prazo fixo no plano gratuito e sem cartão, sujeito a cotas. | Não é hospedagem PHP. Exigiria adaptar e testar PostgreSQL e os procedimentos de backup; hoje validamos SQLite, e MySQL continua pendente. |
| InfinityFree | PHP/MySQL e subdomínio; sem shell/Artisan, cron ou workers. Deploy e mudanças do banco exigem preparação e importação manual. | Não foi homologado; traz dificuldades para migrations, backups e retenção agendada já existentes. |
| Cloudflare Quick Tunnel | URL temporária para uma máquina local; depende da máquina e internet, muda ao reiniciar, sem garantia de disponibilidade. O fornecedor o destina a desenvolvimento/testes. | Somente demonstração/QA. Não é a proposta de hospedagem da votação oficial. |

Fontes oficiais consultadas em 18/10/2026:

- [Oracle: cadastro e verificação](https://docs.oracle.com/en-us/iaas/Content/GSG/Tasks/signingup_topic-Sign_Up_for_Free_Oracle_Cloud_Promotion.htm), [limites Always Free](https://docs.oracle.com/en-us/iaas/Content/FreeTier/freetier_topic-Always_Free_Resources.htm) e [disponibilidade regional](https://www.oracle.com/cloud/free/faq/).
- [Render: modalidade gratuita e persistência](https://render.com/docs/free).
- [Neon: plano gratuito](https://neon.com/pricing).
- [InfinityFree: Laravel e limitações](https://forum.infinityfree.com/t/how-to-install-a-laravel-site-on-infinityfree/118578).
- [Cloudflare: Quick Tunnels](https://developers.cloudflare.com/tunnel/get-started/quick-tunnels/).

O usuário informou que não há servidor disponível na empresa. Próxima opção em avaliação: VM Oracle Always Free para preservar Laravel/MySQL, condicionada ao cadastro, disponibilidade gratuita, endereço público e HTTPS. Não ativar recursos pagos. Se o cadastro ou a capacidade impedirem essa opção, reavaliar a hospedagem; Render/Neon não foi homologado para a votação oficial. Nenhuma dessas alternativas foi contratada ou aprovada para publicação nesta etapa.
