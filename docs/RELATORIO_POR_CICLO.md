# Relatórios e resultados por ciclo

A alternativa 1 organiza a rodada em três partes: classificação pela pontuação, decisão humana e aprovação/divulgação da bonificação. Uma posição alta não significa seleção ou direito automático ao prêmio.

## Onde consultar

1. Abra Relatórios. O ciclo cadastrado mais recentemente é selecionado inicialmente.
2. Use Escolha o ciclo → Ver relatório para mudar de rodada. Nome, tipo, situação e período identificam o contexto.
3. Os indicadores mostram ideias incluídas, pessoas participantes e votos válidos. A mesma pessoa pode votar em várias propostas; por isso pessoas e votos são contagens diferentes. Não se apresenta percentual sem base histórica de pessoas elegíveis.
4. Em ciclo apurado, a classificação mostra posição, título revisado, pontuação de 0 a 100, votos por ideia, decisão da gestão e bonificação. Uma pontuação zero válida aparece como zero; sem os votos mínimos, aparece Votos insuficientes.
5. Ver detalhes abre o ciclo e expande a proposta correspondente, onde o administrador autorizado registra a decisão. O título e a classificação vêm da publicação e apuração congeladas do ciclo; uma mudança posterior no cadastro não reescreve o resultado.
6. Exportar resultado do ciclo baixa um CSV apenas daquela rodada, sem autoria, justificativas privadas ou votos individuais. A exportação fica bloqueada antes da apuração e é exclusiva da administração.

## Antes da apuração

Rascunhos e ciclos abertos/programados mostram situação e contagens agregadas, sem ranking ou pontuação parcial. Mesmo depois do horário final, o administrador precisa usar Encerrar e apurar após o prazo. Um ciclo cancelado não terá apuração.

## Página do ciclo

Após a apuração, a mesma classificação aparece no início da página do ciclo. Os textos extensos e os formulários por ideia ficam em detalhes expansíveis; clicar em Ver detalhes abre o trecho correto. Os formulários de decisão, revisão de alertas, aprovação e divulgação permanecem com suas permissões e validações anteriores.

Funcionários autorizados veem a classificação e a decisão pública. Justificativas administrativas continuam restritas, e bonificações só aparecem para funcionários depois de divulgadas. Não há exibição de autoria ou de quais notas cada funcionário enviou.

## Valores financeiros

- Pendente de aprovação: propostas ainda não aprovadas nem recusadas.
- Total aprovado: inclui as bonificações já divulgadas e exclui as recusadas.
- Divulgado: parcela já publicada para os funcionários.

Não some Total aprovado e Divulgado: o segundo está incluído no primeiro. As situações de cada bonificação aparecem na linha da ideia; Gerenciar decisões e bonificações leva à página do ciclo. O sistema registra os valores, sem executar transferência bancária.

## Indicadores de execução

O botão Indicadores de execução abre a área complementar com os indicadores históricos, evolução mensal, tempo por etapa, benefícios validados e CSV de medições. Esses indicadores continuam com filtros por envio da proposta, setor, situação e categoria. Seu CSV é diferente do CSV de classificação do ciclo e mantém as regras de resultados comprovados.

## Visual e movimento

Os indicadores reutilizam os cartões com ícones/cores do painel. A tabela tem barras proporcionais à pontuação, entrada breve das linhas em sequência e detalhes com transição de opacidade. A preferência de reduzir movimento desativa esses efeitos, preservando os valores finais. No celular, a tabela rola dentro do próprio bloco, sem aumentar a largura da página.

## QA

Teste uma rodada com duas ideias e uma pessoa votando nas duas: deve haver uma pessoa e dois votos válidos. Confira mínimo de votos, zero válido, decisões pendentes e concluídas e bonificações em proposta/aprovação/divulgação/recusa. Troque o ciclo e confirme que os dados e CSV mudam juntos. Repita com funcionário, verificando ausência de dados privados e de prêmios ainda não divulgados. Confira teclado, dois temas, 320/390 px e movimento reduzido.

A implementação não exige migration nem recalcula votos ou prêmios antigos.

## Bonificações por ciclo — acesso administrativo

Em **Relatórios → Bonificações por ciclo**, a administração consulta todas as rodadas apuradas, com filtros por ciclo e período de encerramento da votação. As datas usam o fuso configurado para os ciclos. O resumo considera todos os ciclos filtrados; a listagem apresenta até dez ciclos por página.

Cada ciclo tem seu próprio bloco com período, totais e destaque para a ideia com mais votos válidos (incluindo empates). A classificação segue a pontuação congelada da metodologia, que pode ser diferente da ordem por quantidade de votos. Todas as ideias da rodada são mostradas, mesmo quando não possuem bonificação.

A tabela apresenta posição, ideia, pontuação, votos, destinatário, valor individual e situação. Uma bonificação coletiva ocupa uma linha por destinatário, usando a divisão gravada em `award_allocations`, sem recalcular a partir dos coautores atuais. Registros antigos sem divisão usam o destinatário da própria bonificação. O total do prêmio não é repetido como valor individual de cada coautor.

Os estados são Proposta, Aprovada, Divulgada e Não aprovada. O total aprovado inclui o já divulgado; valores rejeitados não entram nos totais aprovado/pendente. Esses registros não comprovam transferência bancária. A página exige a permissão administrativa também no acesso direto pela URL; funcionários e avaliadores sem acesso administrativo recebem 403. Não apresenta e-mails ou votos individuais.
