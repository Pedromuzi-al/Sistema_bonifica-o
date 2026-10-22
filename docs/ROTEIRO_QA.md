# Roteiro de QA da versão local

## Atualização de 19/10: revisão simples

Para o ciclo de seleção desta rodada, use Enviada → Liberada para votação. Em Revisão de ideias, abra a proposta e use os três botões do início da página: Liberar para votação, Pedir ajuste ou Arquivar. Triagem, mudança para avaliação e critérios técnicos deixaram de ser passos obrigatórios. Cenários antigos abaixo que citam essas etapas são do percurso técnico opcional/legado.

Teste uma proposta recém-enviada com benefícios vazios: título, problema e solução já preenchidos; justificativa e revisão de privacidade liberam diretamente para um ciclo. Teste Pedir ajuste sem preencher publicação: o autor recebe aviso e responde; a ideia volta para nova revisão. Teste Arquivar: mantém histórico e não pode entrar em votação. Funcionários, autor/coautores e pessoas impedidas não revisam a própria ideia. Durante ciclo programado/aberto ou com decisão pendente, pedir ajuste, arquivar e liberar de novo devem ser bloqueados. Conclua voto, apuração, seleção e proposta de bonificação sem registrar avaliação técnica.


Esta versão reúne administração/acesso, avaliação/reconhecimento e documentos/privacidade. Execute os cenários abaixo com contas de administrador, avaliador, autor, coautor e funcionário sem participação na proposta. Use dados fictícios. Registre cenário, passos, resultado esperado, resultado observado, tema, tamanho da tela e evidência quando encontrar um defeito.

O fluxo principal de seleção antes da implementação tem um roteiro próprio em [CICLO_SELECAO.md](CICLO_SELECAO.md). Comece por ele para avaliar a nova lógica; os cenários de faixas e resultados abaixo cobrem o reconhecimento posterior.

## 1. Preparação

1. Na pasta do projeto, instale dependências se necessário: `composer install` e `npm ci`.
2. Execute `php artisan migrate` e `npm run build`.
3. Inicie com `php artisan serve`. Para reconstrução automática dos recursos durante desenvolvimento, use `npm run dev` em outro terminal.
4. Os anexos estão habilitados no `.env` local desta entrega (`IDEA_ATTACHMENTS_ENABLED=true`). Em outro ambiente, habilite a opção e execute `php artisan config:clear`.
5. Para acompanhar tarefas agendadas localmente, use `php artisan schedule:work` em outro terminal. A exclusão de documentos só atinge arquivos com decisão registrada e prazo vencido, sem preservação obrigatória.
6. Os horários dos ciclos são digitados e exibidos em `America/Sao_Paulo` por padrão (`CYCLE_TIMEZONE`). O sistema converte para o fuso interno do servidor ao salvar. Não é necessário alterar o fuso do banco para testar hoje.
7. Para testar e-mail sem SMTP, use `MAIL_MAILER=log` e confira o e-mail no arquivo local `storage/logs/laravel.log`. O link é uma credencial temporária: não compartilhe logs ou capturas que o exponham. A entrega em uma caixa real exige um serviço de e-mail, ainda não escolhido.
8. Use as contas já existentes no seu ambiente. Os acessos demonstrativos do seeder são somente para testes locais; não execute o seeder de demonstração em uma base com dados reais.

## 2. Interface, temas, celular e teclado

1. Abra Painel, Ideias, Votações, Avaliação, Administração, Perfil e Relatórios no tema claro.
2. Passe o mouse sobre cartões, botões, links, ações da conta, menu e permissões. Formulários, tabelas e cartões de conteúdo permanecem na mesma posição. Os cartões de indicadores do Painel e da Administração sobem 5 px com mouse e voltam suavemente; botões e links continuam legíveis.
3. Confira o menu lateral à esquerda no desktop e a indicação da página atual. Ao passar o mouse, ela deve continuar identificada. Atalhos de Administração, Relatórios e Auditoria respeitam as permissões.
4. Repita no tema escuro. Não deve aparecer um fundo branco com texto claro nos controles de navegação.
5. Recarregue: a preferência de tema deve ser mantida.
6. Em 390 px e 320 px de largura, confira logo, menu, formulários, tabelas e ações. Tabelas extensas podem rolar dentro do próprio painel; a página não deve exigir rolagem horizontal.
7. Use Tab, Shift+Tab, Enter e Espaço. No celular, abra o menu: o foco deve permanecer nele, o conteúdo de fundo fica indisponível e Escape fecha o menu e devolve o foco ao botão. Teste também o fundo escurecido e o botão Fechar menu. Use Pular para o conteúdo ao navegar pelo teclado.
8. Ative a preferência do sistema de reduzir movimento. Animações decorativas e transições devem ser reduzidas.

## 3. Setores e equipes

1. Entre como administrador e abra Administração → Setores. Equipes têm acesso separado no mesmo painel.
2. Confira o catálogo ativo: Material Didático, Sucesso do Cliente, Tecnologia, Produto e Administração. Teste criar um setor fictício, editar nome/descrição, desativar e reativar; ao concluir, desative o setor fictício.
3. Tente repetir um nome: deve haver validação.
4. Crie uma equipe com setor de referência e participantes; crie outra sem setor para uma equipe entre setores.
5. Edite participantes e desative a equipe. As associações existentes devem continuar no histórico.
6. Tente associar uma nova pessoa a uma equipe inativa, ou usar um setor inativo numa nova ideia: deve ser recusado.
7. Remova todas as seleções e provoque um erro em outro campo. Ao corrigir, as seleções removidas não devem reaparecer.
8. Entre como funcionário e tente acessar as ações administrativas diretamente pela URL: nenhum cadastro ou alteração deve ocorrer.

## 4. Pessoas e primeiro acesso

1. No modo manual, abra Administração → Pessoas e acessos → Cadastrar pessoa e informe nome, e-mail, setor/equipes e perfil. Não há senha no formulário de dados/permissões.
2. Depois de salvar, a conta está pendente. Abra Redefinir senha, preencha Nova senha/Confirmar senha e clique em Concluir. Entre em outra janela com essa senha e o e-mail; o perfil define os acessos e o e-mail não é marcado como verificado.
3. Cancele uma tentativa antes de salvar: os campos devem ser limpos. Informe senhas diferentes: o erro deve aparecer, sem salvar ou preencher novamente a senha.
4. Para uma conta já ativa, use o mesmo procedimento. A senha anterior e as sessões anteriores deixam de funcionar; os acessos/permissões não mudam. Não registre senhas em capturas, logs ou evidências.
5. Tente redefinir a senha de uma conta bloqueada/desativada: a conta precisa ser reativada primeiro. Funcionários não podem redefinir a senha de outras pessoas.
6. Para testar a modalidade opcional por e-mail, configure entrega por e-mail e seu transporte. Cadastre uma pessoa, abra o convite em janela anônima e defina a senha: o e-mail é confirmado. Links reutilizados/expirados e convites de contas bloqueadas devem falhar. Reenvio invalida o token anterior.
7. No autocadastro, habilitado somente quando desejado, confirme acesso de Funcionário e ausência de privilégios administrativos, mesmo se uma requisição tentar enviar `role=admin`. Sem configuração prévia de perfis, não deve haver conta parcialmente criada.

## 5. Permissões, bloqueio, reativação e perfil

1. Edite uma pessoa e confira perfil e permissões adicionais. Permissões concedidas pelo perfil não são removidas desmarcando um acesso extra.
2. Abra uma sessão da pessoa em outra janela. Bloqueie pelo administrador, com motivo: a sessão deve perder o acesso.
3. Reative com justificativa. Sessões antigas não devem voltar a funcionar; faça novo login.
4. Desative sua conta pelo próprio perfil em uma conta de teste. Confirme que o histórico é preservado.
5. Como administrador, revise os dados e permissões da conta desativada antes de reativá-la.
6. Tente bloquear a própria conta ou retirar o último administrador ativo e verificado: deve ser impedido.
7. No próprio perfil, tente mudar o e-mail sem a senha atual e com senha errada: deve falhar. Com a senha correta, a confirmação anterior deixa de valer e os acessos antigos são revogados. Alterar somente o nome não exige senha.
8. Ao corrigir o próprio e-mail pelo perfil, a sessão atual permite acompanhar a nova confirmação.
9. Teste “Esqueci minha senha”: token válido permite redefinir; token expirado ou reutilizado falha. Sessões anteriores são revogadas.
10. Uma pessoa sem perfil pode aguardar liberação, mas não deve consultar publicações corporativas ou administrar o sistema.

## 6. Envio da ideia

1. Como funcionário, abra Tenho uma ideia/Nova ideia.
2. Clique Continuar sem preencher: a etapa deve permanecer e indicar o campo obrigatório.
3. Preencha título, problema e solução; avance.
4. Selecione o setor de origem e pelo menos um beneficiado. Teste “Todos os setores”.
5. Avance, volte e confira que os dados continuam preenchidos.
6. Benefícios estimados, recursos e observação são opcionais. Teste enviar sem preenchê-los.
7. Clique Conferir minha ideia. Nada deve ser gravado antes da confirmação.
8. Clique Quero corrigir, ajuste e volte à revisão.
9. Clique Enviar minha ideia. Confira o código, situação Enviada, aviso privado e histórico.
10. Outro funcionário não deve acessar a proposta privada. A autoria vem da sessão, independentemente de campos extras enviados na requisição.

## 7. Ideia em grupo e divisão

1. Antes de encerrar a triagem, um administrador com acesso à identidade abre a proposta.
2. Em Participantes e divisão do reconhecimento, inclua autor e coautores ativos.
3. Distribua exatamente 100%, sem repetir pessoas. O autor principal deve permanecer incluído.
4. Teste 60%/40% e confira o aviso de participação e acesso do coautor à proposta em Minhas ideias.
5. Tente total diferente de 100%, autor ausente ou pessoa repetida: deve falhar.
6. Após avançar para avaliação ou abrir a primeira votação, a composição fica fixa.
7. Autor e coautores não podem avaliar, votar ou aprovar o próprio reconhecimento.
8. A lista privada de participantes não deve aparecer para funcionários externos na publicação ou votação.

## 8. Critérios e avaliação técnica

1. Como administrador, cadastre critérios técnicos com orientação e peso positivo.
2. Como avaliador sem conflito, avance a proposta de Enviada → Em triagem → Em avaliação, sempre justificando.
3. Preencha todos os critérios ativos com notas de 1 a 5 e registre parecer favorável, desfavorável ou pedido de esclarecimentos.
4. Tente omitir critério ou enviar uma nota fora da escala: deve ser recusado.
5. Peça esclarecimentos, entre como autor/coautor e responda privadamente; confira o histórico.
6. Edite/desative um critério após um parecer. A nota, descrição e peso antigos devem continuar no parecer histórico.
7. Um parecer mais recente desfavorável impede a aprovação, mesmo que exista um parecer favorável anterior.
8. Declare conflito de interesse em uma proposta de teste: avaliações e votos devem ficar impedidos.
9. Rejeite outra proposta com justificativa e confira a comunicação ao autor.

## 9. Implementação, medição e publicação

1. Após seleção justificada no novo ciclo, a proposta inicial fica aprovada; no percurso técnico anterior, aprove após o parecer favorável.
2. Cadastre plano, responsável e prazo. Só então avance para implementação.
3. Registre resultados reais, limitações e indicadores. Exemplo: Tempo, unidade minutos, antes 20, depois 10, período de observação e evidência.
4. Teste adicionar/remover indicadores. Os campos ocultos não devem impedir o envio.
5. Avance para validação, justifique a validação das evidências e conclua.
6. Se editar resultados, a validação anterior perde validade; valide novamente antes de concluir.
7. Em Revisar publicação, escreva título, problema, solução, resultados e categoria sem dados pessoais.
8. Confirme a revisão de privacidade e publique.
9. Como funcionário externo, confira que somente os textos revisados aparecem. Conteúdo interno e documentos continuam privados.

## 10. Documentos, revisão, retenção e exclusão

1. Numa proposta em etapa permitida, envie PDF, PNG, JPG ou TXT com até 10 MB. O limite é dez arquivos não excluídos por proposta.
2. O nome original não é usado no armazenamento nem no download; o sistema usa um identificador.
3. Autor/coautores e avaliadores autorizados podem baixar os documentos privados. Um funcionário externo, mesmo com a publicação aberta, não pode.
4. Um administrador com acesso à auditoria abre Revisar acesso, retenção ou exclusão. Se necessário, confirma a senha antes de preencher o formulário.
5. Registre justificativa de pelo menos 20 caracteres e confirme a revisão.
6. Aprove para uso privado: isso não torna o documento público.
7. Restrinja: o download fica limitado à revisão administrativa, inclusive para participantes.
8. Defina um prazo de retenção aprovado, se houver. Nenhum prazo é presumido pelo sistema.
9. Marque preservação obrigatória: o prazo pode vencer, mas o arquivo não deve ser excluído.
10. Tente excluir um arquivo preservado: deve ser recusado. Para retirar a preservação, faça uma revisão própria e justificada antes da exclusão.
11. Em um arquivo fictício sem preservação, escolha Excluir arquivo definitivamente. O arquivo sai do armazenamento; o identificador, a decisão e a auditoria permanecem. O download passa a falhar.
12. Simule a rotina: `php artisan app:purge-private-documents`. Sem `--execute`, nada é excluído.
13. Depois de vencer um prazo previamente aprovado, execute `php artisan app:purge-private-documents --execute` e confira o resultado. O scheduler faz essa conferência diária quando estiver em execução.
14. Arquivos ainda não revisados e sem prazo não são apagados automaticamente. Falhas de exclusão ficam pendentes para nova tentativa.
15. Revisão de privacidade não equivale a antivírus: avalie os documentos conforme o procedimento interno da empresa.

## 11. Reconhecimento posterior por resultados: faixas e elegibilidade

1. Como administrador, abra Votações → Novo ciclo e use Criar reconhecimento por resultados comprovados. Para seleção antes da execução, use o formulário padrão e o roteiro CICLO_SELECAO.md.
2. Início e fim podem ser hoje, no fuso indicado na tela. Para um teste curto, escolha início daqui a cinco minutos e fim daqui a dez minutos, selecione as ideias e abra o ciclo antes do início. A apuração é liberada no fim do período, inclusive no mesmo dia.
3. Configure o indicador com nome/unidade iguais aos resultados publicados. Para Tempo/minutos do exemplo, use direção diminuir e meta 10.
4. Os pesos das dimensões e dos critérios de voto devem somar 100%.
5. Configure reconhecimento principal ou faixas opcionais. Exemplo: 90–100 pontos → Excelência, R$ 101,01; 70–89,99 → Contribuição, R$ 50,00.
6. Faixas sobrepostas são recusadas. Cada ideia recebe no máximo uma categoria.
7. Em cada faixa, experimente mínimos de votos, nota técnica, resultados e categoria da publicação. Os requisitos são cumulativos; o mínimo geral de votos continua valendo.
8. Salve o rascunho e selecione somente ideias concluídas, revisadas e com resultados validados e avaliação favorável.
9. Abra antes do início. Confira o resumo das regras congeladas.
10. Após aberto, alterações na metodologia não devem ser aceitas. Um rascunho pode ter nome/período corrigidos ou ser cancelado.

## 12. Votação e impedimentos

1. Antes do início, o funcionário deve ver quando poderá votar; depois do fim, a votação deve estar encerrada.
2. Durante o período, um funcionário sem participação/conflito abre Conhecer a ideia e votar.
3. Confira a situação da implementação: Ainda será implementada, Em implementação ou Implementada. Ela corresponde ao registro na abertura do ciclo. Nenhuma nota deve vir marcada, e autoria e documentos não devem aparecer.
4. Responda aos critérios, confira as respostas, volte para corrigir e confirme.
5. Confira o recibo. Tente votar novamente: não pode registrar outro voto.
6. Autor, coautores e pessoas com conflito declarado não podem votar.
7. Se houver abstenção, é necessário responder pelo menos um critério; os pesos respondidos são redistribuídos.
8. Um administrador com auditoria pode invalidar um voto antes da apuração, com justificativa. O registro permanece e não libera um novo voto.

## 13. Apuração do ciclo de resultados, reconhecimento e encerramento sem prêmio

1. Após o prazo, clique Encerrar e apurar.
2. Confira notas, classificação, mínimos e propostas de reconhecimento. Ausência do mínimo de votos não gera pontuação final/prêmio.
3. Confira que requisitos específicos de cada faixa também são respeitados.
4. A quantidade de vencedores deve obedecer ao limite por categoria.
5. Para divisão 60%/40% de R$ 101,01, confira R$ 60,60 e R$ 40,41. As parcelas precisam somar o total.
6. Havendo alerta de notas idênticas, um administrador autorizado deve registrar revisão antes da aprovação. O alerta, por si só, não afirma fraude.
7. Aprove com justificativa e divulgue em uma ação separada. O participante consulta seu reconhecimento/parcela no painel.
8. Em outro cenário, recuse um reconhecimento proposto com justificativa.
9. Finalize ideias sem prêmio e aquelas cujo prêmio foi recusado, com justificativa. Não pode haver reconhecimento pendente.
10. Tente aprovar duas vezes, divulgar sem aprovação ou finalizar por um ciclo anterior: deve ser impedido ou preservar o estado do ciclo mais recente.
11. Não existe pagamento automático: a plataforma registra a decisão e o valor de reconhecimento.

## 14. Indicadores, CSV, notificações e auditoria

1. Abra Relatórios como administrador e aplique datas, setor, situação e categoria.
2. Confira recebidas, resultados validados, tempo de execução e reconhecimentos aprovados.
3. Confira evolução mensal, distribuição por situação, tempos por etapa e pessoas/votos por ciclo.
4. Nos benefícios, compare apenas o mesmo setor de origem, indicador, unidade e período. Redução aparece como diferença negativa; não some medições sobrepostas como se fossem benefícios independentes.
5. Exporte CSV e confira setor de origem e período das medições. Dados não validados não podem ser apresentados como resultado comprovado.
6. Autoria e votos individuais não devem aparecer no CSV. Conteúdo que poderia virar fórmula em planilha deve ser neutralizado.
7. Em Avisos, filtre não lidos, marque um e marque todos. Outro funcionário não pode acessar seus avisos.
8. Em Auditoria, confira alterações de acesso, critérios, documentos e decisões. Apenas quem possui a permissão específica deve acessar.

## 15. Solicitações de privacidade

1. Abra Meu perfil → Meus dados e privacidade; a seção deve aparecer uma única vez.
2. Baixe seus dados após confirmar a senha. A exportação deve conter somente seus dados e sua própria parcela dos reconhecimentos publicados.
3. Confirme a senha antes de preencher uma solicitação. Envie um motivo e acompanhe a situação no perfil.
4. Não deve ser criada outra solicitação pendente para a mesma pessoa.
5. Como administrador com auditoria, abra Administração → Solicitações de privacidade.
6. Decida preservação justificada ou anonimização, registrando dados preservados, prazo e resposta ao solicitante.
7. Antes de anonimizar, conclua propostas e reconhecimentos pendentes e revise textos/documentos. Utilize a revisão de documentos para restringir ou excluir arquivos pessoais desnecessários.
8. Anonimize uma conta fictícia sem pendências: nome/e-mail e dados de acesso são substituídos, sessões e permissões revogadas e notificações removidas.
9. Tente reativar ou editar essa conta: deve falhar. Outro cadastro será necessário para um retorno.
10. A anonimização da conta não apaga automaticamente textos corporativos, auditoria e cópias de backup. A análise desses registros e sua retenção dependem do procedimento aprovado pela empresa; o sistema não inventa um prazo legal nem remove todo o histórico empresarial.

## 16. Backup e restauração local

1. Termine as alterações em andamento antes de copiar banco e documentos.
2. Execute `php artisan app:backup`.
3. Copie o caminho impresso e execute `php artisan app:check-backup-restore "CAMINHO" --database=qa_restauracao_test`.
4. Em SQLite local, o comando restaura banco e documentos em `storage/app/restore-check/qa_restauracao_test`, confere integridade, contagens e hashes dos arquivos. A base da aplicação não é substituída.
5. Um destino já existente é recusado; use outro nome para repetir.
6. MySQL exige cliente, credenciais e banco de teste próprios, conforme README. As credenciais MySQL disponíveis nesta sessão não permitiram a validação desse perfil.
7. Backups não são criptografados pelo comando. Dados excluídos podem continuar em cópias anteriores até o prazo de retenção da política de backup.

## 17. Repetição automática

```bash
php artisan test
npm test
npm run test:browser
```

O teste de navegador requer Google Chrome instalado. É possível indicar outro executável compatível com `QA_CHROME_EXECUTABLE`. Ele recompila os recursos, cria banco SQLite, contas e armazenamento temporários, inicia um servidor em porta livre e remove o ambiente ao terminar. Não usa a base de trabalho. As capturas e o resultado JSON ficam em `storage/app/qa-reports/`.

O teste de navegador cobre páginas administrativas nos dois temas, hovers, cadastro por etapas no celular, menu/teclado e documento privado com confirmação de senha, restrição e exclusão. O fluxo de avaliação até reconhecimento também foi percorrido no navegador em ambiente isolado; as regras de prazo e cálculo são verificadas na suíte PHP. O teste automatizado não substitui seu QA exploratório e a avaliação com pessoas usando o sistema.


## 17. Listagem de ciclos e navegação lateral

1. Clique em Votações no menu lateral: deve abrir somente a listagem, sem o formulário de cadastro. Confira nome, tipo, período, situação e acesso a cada ciclo.
2. Como administrador, clique Novo ciclo. Deve abrir uma página própria; Voltar para os ciclos retorna à listagem. Confira os formulários de seleção e de reconhecimento por resultados.
3. Provoque um erro de data: deve permanecer na criação, com os valores preservados e as mensagens de validação. Salve corretamente e confira o novo rascunho na listagem.
4. Numa base de testes vazia, a listagem informa que nenhum ciclo foi cadastrado e orienta criar o primeiro. Não apague ciclos da base de trabalho para provocar esse cenário.
5. Como funcionário, confira apenas ciclos abertos/encerrados. Não deve aparecer Novo ciclo, nem ser permitido abrir `/cycles/create` diretamente. Se não houver ciclos disponíveis, deve haver uma mensagem de estado vazio.
6. Nos temas claro e escuro, confira menu lateral fixo no desktop, perfil, alternância de tema e saída. No celular (320/390 px), o menu deve abrir como uma gaveta, sem causar rolagem horizontal da página. Tabelas podem rolar dentro do próprio painel.


### Complementos da revisão autônoma

- Ao escolher propostas para um rascunho, ideias em outra votação ou com decisão anterior pendente devem estar desabilitadas e explicar o motivo. Depois de resolver a pendência, recarregue e confira a liberação. A confirmação deve continuar recusando uma requisição forçada que desrespeite o impedimento.
- Com foco no menu ou em seu botão, alterne entre larguras de 1023 e 1024 px. O foco deve ficar em um controle visível. Com o menu aberto no celular, a barra superior também deve ficar indisponível. Confira Escape, clique no fundo e Tab/Shift+Tab.
- O progresso e as evidências desta revisão estão em [STATUS.md](../STATUS.md).


## 18. Administração por áreas

1. Em Administração, confira os cartões Setores, Pessoas e acessos, Critérios técnicos e Equipes. O formulário de critério não deve aparecer no painel principal.
2. Abra Setores. A tabela mostra ativos e inativos, pessoas vinculadas e ideias de origem. Filtre pelo nome e pela situação e use Limpar.
3. Use Novo setor, salve e confira o detalhe. Não existe campo Disponível para protocolos.
4. No detalhe, alterne entre Vinculados a este setor, Não vinculados a este setor e Todas as pessoas. Busque por nome/e-mail.
5. Vincule uma conta fictícia e confirme a operação. Se ela já estiver em outro setor, o setor de referência será transferido. As equipes, permissões e os setores das ideias anteriores devem permanecer preservados. Confira a auditoria `sector_membership_updated`.
6. Desvincule a pessoa e confira que fica sem setor de referência. Uma tentativa de remover um vínculo de um setor ao qual ela já não pertence deve falhar.
7. Inative o setor pela edição: novos vínculos são recusados, mas os vínculos anteriores continuam visíveis e podem ser removidos. Contas desativadas aparecem para consulta histórica, sem ações de vínculo.
8. Abra Pessoas e acessos: confira a tabela de contas, setor/equipes, perfil, quantidade de permissões efetivas e situação. Gerenciar acesso abre o cadastro com perfis, permissões, bloqueio e reativação já existentes.
9. Abra Critérios técnicos. Confira a listagem e os filtros, sem formulário de criação embutido. Novo critério técnico abre o cadastro separado; salvar retorna à lista. Editar permite alterar texto/peso e inativar o critério, preservando os pareceres antigos.
10. Como funcionário ou avaliador sem administração, tente abrir as novas URLs e alterar um vínculo: o acesso deve ser negado no servidor.
11. Nos temas claro e escuro e em 320/390 px, confira os cartões, formulários e tabelas. Tabelas largas podem rolar dentro do painel.


## 19. Visual e animações dos indicadores

1. No Painel, confira a apresentação de boas-vindas, o destaque em azul e os cinco indicadores. Na Administração, confira os indicadores de gestão. Os valores continuam vindo do servidor; não foram acrescentadas porcentagens ou tendências fictícias.
2. Com mouse, passe sobre um indicador: ele sobe 5 px, ajusta sombra/borda e volta à posição original ao sair. Não deve mover os cartões de conteúdo, módulos, formulários ou tabelas.
3. Os indicadores aparecem com uma breve transição de opacidade em sequência. Abertura/fechamento do menu no celular desliza suavemente; em Relatórios, a linha do gráfico é desenhada ao carregar.
4. Ative Reduzir movimento no sistema/navegador: indicadores não devem se deslocar nem fazer transição de entrada; gráficos devem aparecer completos; o menu deve abrir sem deslizar.
5. Em um celular com toque, os indicadores não devem se deslocar por hover. Confira as telas em 320 e 390 px, nos dois temas, e verifique foco de teclado e leitura dos textos.
6. A caixa Atalhos do fim do Painel continua removida. A seção Por onde começar? foi adaptada dentro da apresentação inicial.


## Primeira rodada sem confirmação de e-mail

Use `EMAIL_VERIFICATION_REQUIRED=false` e `ACCOUNT_INVITATION_DELIVERY=manual`. Contas liberadas entram sem confirmar e-mail; papéis, bloqueios e impedimentos continuam obrigatórios. Cadastre uma pessoa e use Redefinir senha → Nova senha/Confirmar senha → Concluir. Entre em outra janela, confirme que não houve e-mail nem confirmação artificial do endereço e que o perfil Funcionário não acessa a administração. Confira revogação da senha/sessões anteriores e auditoria sem credenciais.

O roteiro operacional está em [ENTREGA_VOTACAO_SEXTA.md](ENTREGA_VOTACAO_SEXTA.md); o nome conserva o histórico, mas o prazo atual é novembro. Cenários de verificação automática exigem ativar explicitamente confirmação e entrega por e-mail.

## Regressão das correções de segurança e resultados — 21/10/2026

- Abra duas sessões da mesma conta. Troque a senha em Meu perfil: a atual continua, a outra perde o acesso, e recuperação antiga deixa de funcionar.
- Em uma conta de teste, faça cinco confirmações de senha erradas no minuto. A tentativa seguinte deve aguardar o limite; outra conta no mesmo IP continua independente.
- No modo por e-mail, peça recuperação para conta existente, inexistente e repetida: a mensagem pública deve ser a mesma. Não realize esse ensaio com e-mails reais sem combinar com os titulares.
- Em um resultado de teste, tente usar `1e309`, ou valores que causem estouro no cálculo da diferença/percentual. Deve aparecer validação, sem erro 500 e sem substituir resultados anteriores.
- Reabra uma implementação que já tinha resultado validado. O plano volta a ficar pendente, a validação anterior é retirada e o histórico é preservado. Concluir sem validar novamente deve falhar.

## Exclusão administrativa de ideias em lote

1. Como administrador, abra Ideias → Excluir ideias. O pop-up mostra as ideias da página e respeita os filtros/paginação da listagem.
2. Marque duas ideias de teste sem ciclos/bonificações/execução e clique em Continuar. Informe motivo com dez caracteres ou mais, marque a confirmação e use Confirmar exclusão.
3. Confira que as ideias somem da listagem, painel, revisão e relatórios correntes; seus endereços retornam 404, inclusive para o autor. Auditoria, histórico e documentos privados são preservados: não é exclusão definitiva nem atendimento automático de anonimização.
4. Abra o pop-up com ideias vinculadas a ciclos, votos, bonificações, execução/resultados ou documentos sob preservação obrigatória: devem aparecer desabilitadas com motivo. Tentar forçar esses IDs pelo servidor deve rejeitar o lote inteiro, sem excluir as demais selecionadas.
5. Teste Cancelar, Fechar e Escape sem alterações. Confira foco dentro do pop-up e retorno ao botão; repita em celular e nos temas claro/escuro. A confirmação deve ser explícita após selecionar.
6. Como funcionário e avaliador, confira ausência do botão e recusa da rota mesmo conhecendo IDs. Listas vazias, IDs repetidos/inexistentes e pedido sem confirmação/motivo devem ser rejeitados.

A migration `2026_10_19_170000_add_soft_deletes_to_ideas.php` precisa ser aplicada em cada nova instalação antes de usar esta versão. Foi aplicada na base local após backup e restauração em cópia isolada. Nenhuma ideia real foi excluída durante o desenvolvimento: os testes usam banco descartável.


## Animação das notas na votação

Abra um ciclo em período de votação com uma conta sem participação na proposta. Confira que nenhuma nota vem selecionada. Marque cada opção: o cartão deve mudar suavemente de cor/borda e dar um breve destaque, com o mesmo tratamento para notas baixas e altas. Trocar uma resposta deve desmarcar a anterior sem mover a posição da pergunta. Com mouse, opções não selecionadas têm hover discreto; no celular, não há levantamento por hover.

Use Tab e setas para alternar notas: o foco deve aparecer no cartão inteiro, sem contornos duplicados na seleção por mouse. Em claro e escuro, confira a legibilidade das opções marcadas e desmarcadas. Ative redução de movimento no sistema/navegador: entrada, destaque de escala, hover com deslocamento e transições devem ficar desativados. Confira o resumo, volte para corrigir, confirme o voto e confira impedimento de duplicação como antes.


## Relatório por ciclo e nova classificação

Siga [RELATORIO_POR_CICLO.md](RELATORIO_POR_CICLO.md). Confira ciclo mais recente e troca de rodada; contagem de pessoas distinta da de votos; ranking somente após apuração; zero válido e votos insuficientes; separação entre posição, decisão e bonificação. Abra Ver detalhes e registre uma decisão autorizada. Compare proposta pendente, aprovada, divulgada e recusada, sem somar aprovado e divulgado. Como funcionário, confira que justificativas privadas e bonificações não divulgadas não aparecem. Exporte o CSV da rodada e confira seu escopo. A área Indicadores de execução mantém a exportação anterior. Repita nos dois temas, teclado, celular e com redução de movimento.

### Indicadores gerais de execução — apresentação

1. Em Relatórios, abra **Indicadores de execução**. Confira os quatro cartões e as seções de distribuição, evolução, participação, tempo por etapa, indicadores validados e propostas.
2. Aplique período, setor, status e categoria. A tela deve continuar nos indicadores gerais; **Limpar filtros** restaura o recorte completo. Um recorte sem propostas deve mostrar mensagens de ausência de dados.
3. Confira os números das barras com os textos. Distribuição é percentual do total; tempos são relativos à etapa mais demorada. Minutos e horas não devem aparecer arredondados como “0,0 dias”. Sem execuções concluídas, o tempo médio mostra “—”.
4. Com apenas um mês de contribuições, confira a coluna e o valor no gráfico. Abra **Consultar valores por mês** com mouse e teclado.
5. Repita nos temas claro e escuro e no celular. Somente as tabelas podem rolar horizontalmente; o restante deve caber na tela. Ative redução de movimento no dispositivo e confirme que os gráficos continuam completos, sem animação.
6. Exporte o CSV após filtrar e confira se o recorte corresponde à tela. Autoria e votos individuais continuam fora da exportação.

### Bonificações por ciclo — administração

1. Como administrador, abra **Relatórios → Bonificações por ciclo**. Confira os cartões de totais e os blocos separados de cada ciclo apurado.
2. Configure mais de uma ideia premiável na rodada, conclua a votação, selecione as propostas e confira todas na tabela do ciclo. Ideias sem prêmio também devem aparecer, com “Sem bonificação”.
3. Em uma ideia com coautores, confira se cada destinatário tem uma linha e se a soma dos valores corresponde ao prêmio da ideia. Alterações posteriores de participantes não devem modificar a divisão histórica.
4. Aprove e divulgue bonificações e confira a situação de cada linha. Rejeitadas não entram nos totais aprovado/pendente; divulgado já está incluído no aprovado.
5. Compare quantidade de votos e pontuação. O destaque por quantidade de votos não precisa ser o primeiro da classificação. Empates na quantidade devem mostrar todas as ideias empatadas.
6. Filtre por ciclo e período de encerramento; limpe os filtros. Ciclos abertos e rascunhos não devem apresentar resultados nesta página.
7. Como funcionário e avaliador sem acesso administrativo, tente `/reports/awards` diretamente: deve retornar acesso negado. Verifique temas, teclado e celular.
