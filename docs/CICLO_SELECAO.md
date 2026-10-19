# Ciclo de seleção de ideias

O ciclo agora pode ajudar a escolher o que implementar. Não exige implementação, indicador medido nem parecer técnico completo para receber votos. A empresa pode usar somente seleção, reconhecimento financeiro pela ideia ou reconhecimento posterior pelos resultados. O valor inicial é zero, sem bonificação financeira.

## Passo a passo para testar

Use dados fictícios e três contas: autor, administrador sem participação na proposta e funcionário votante sem conflito. Um avaliador também pode fazer a triagem e revisar a publicação. A decisão após a votação cabe a um administrador sem conflito.

1. **Registrar:** como funcionário, abra Tenho uma ideia. Informe título, problema, sugestão e setores. Benefícios estimados e recursos são opcionais. Confira e envie. A proposta começa privada, na situação Enviada.
2. **Fazer a triagem:** com a conta responsável, abra a proposta em Avaliação e atualize a etapa para Em triagem, justificando. Confira compreensão, propostas semelhantes e possibilidade de divulgação. Se faltar informação, use a solicitação de esclarecimentos; se houver duplicação ou inviabilidade, registre a justificativa apropriada.
3. **Revisar para votação:** na própria proposta, use Revisar proposta para votação. Edite título, problema, solução, benefícios esperados e categoria. Remova dados pessoais e conteúdo confidencial, marque a confirmação e publique. Nenhum anexo privado será incluído. Não é preciso avançar para avaliação técnica completa ou implementar.
4. **Criar o ciclo:** como administrador, abra Votações → Novo ciclo → Criar ciclo de seleção de ideias. Informe nome, início, fim, limite de ideias que podem avançar e mínimo de votos. Os horários são de Brasília por padrão. Para testar hoje, programe início daqui a cinco minutos e fim daqui a dez minutos.
5. **Configurar o reconhecimento opcional:** em Reconhecimento pela ideia e regras adicionais, mantenha zero para seleção sem dinheiro. Um valor positivo significa uma proposta de bonificação por ideia selecionada, dividida pelos percentuais dos participantes e sujeita a aprovação posterior. Também é possível ajustar nota mínima, abstenção e limite de notas idênticas que gera alerta.
6. **Confirmar:** salve o rascunho, marque as propostas revisadas e confirme a programação antes do início. Se perder o horário, edite o período do rascunho. A abertura fixa perguntas, publicação, situação de implementação e divisão dos participantes. Uma ideia não pode estar simultaneamente em outra votação; a decisão pendente de um ciclo de seleção anterior precisa ser concluída.
7. **Votar:** durante o período, outro funcionário abre a proposta pelo ciclo. A tela identifica Ainda será implementada, Em implementação ou Implementada, conforme o registro da abertura. Para a primeira situação, avisa: “Esta ideia ainda não foi implementada. Avalie o benefício esperado.” Responda, confira e confirme. Autor, coautores e pessoas com conflito não podem votar. O voto é único por pessoa, ideia e ciclo.
8. **Apurar:** após o fim, o administrador usa Encerrar e apurar. A classificação orienta prioridades; não aprova automaticamente propostas nem gera prêmios. A etapa de execução da ideia continua preservada.
9. **Decidir:** na ideia dentro do ciclo, escolha Selecionar para avançar ou Manter no banco de ideias. Justifique com pelo menos dez caracteres, explicando viabilidade e recursos. Selecionar exige votos e pontuação mínimos, ausência de parecer desfavorável mais recente, limite disponível e revisão de eventual alerta. Uma decisão registrada não pode ser trocada pelo formulário.
10. **Executar:** uma proposta ainda na triagem/avaliação/banco passa para Aprovada quando selecionada. Abra Acompanhar a ideia e definir a execução e cadastre plano, responsável e prazo. Só então avance para implementação. Se a proposta já estiver em execução ou concluída, a seleção não regride sua etapa.
11. **Retomar depois:** uma proposta inicial não selecionada passa para No banco de ideias, com decisão e histórico preservados. Pode participar de outro ciclo após resolver a decisão anterior, sem precisar ser implementada. “Manter no banco” não interrompe a execução de uma ideia que já estava sendo implementada.
12. **Reconhecer a ideia:** se foi configurado um valor positivo, a seleção cria uma proposta de reconhecimento. O administrador aprova com justificativa e divulga separadamente. Essa divulgação não finaliza a execução. Com valor zero, nenhum registro financeiro é criado. O sistema não efetua pagamentos.
13. **Comprovar resultados depois:** acompanhe a execução, registre medições, avance para validação, valide evidências e conclua. A avaliação técnica pode ser registrada após a seleção nas etapas de aprovação, implementação, validação ou conclusão; ela não inventa notas a partir dos votos e não muda a etapa da ideia.
14. **Reconhecer resultados:** revise e publique os resultados comprovados na página da ideia. Em Votações → Novo ciclo, use Criar reconhecimento por resultados comprovados. Esse tipo mantém critérios técnicos, metas, pesos e faixas anteriores. Uma publicação de benefício esperado não substitui a revisão dos resultados. Conclua votação, apuração, aprovação e divulgação conforme as regras desse ciclo.

## Como a votação é calculada

As perguntas padrão são Utilidade (34%), Relevância (33%) e Benefício esperado (33%). Cada nota de 1 a 5 é convertida para 0 a 100: `(nota − 1) ÷ 4 × 100`. Calcula-se a média ponderada das respostas de cada voto e depois a média dos votos válidos. Abstenções, quando permitidas, redistribuem os pesos entre as perguntas respondidas; é obrigatório responder pelo menos uma.

Sem o mínimo de votos não existe pontuação final nem possibilidade de seleção neste ciclo, mas a ideia pode ficar no banco para outra oportunidade. O desempate de classificação usa o código da ideia em ordem crescente. A equipe pode selecionar uma proposta que não ficou em primeiro lugar, respeitando os requisitos e o limite, e deve justificar a decisão. A classificação não garante execução ou bonificação.

O alerta de notas idênticas exige revisão administrativa antes de selecionar. Ele não invalida automaticamente os votos e não afirma fraude. Uma revisão que determine novo ciclo impede a seleção no ciclo comprometido; registre a decisão de manter a proposta no banco e abra outro ciclo.

## Preservação de histórico

Os ciclos existentes continuam sendo reconhecimento por resultados e não são convertidos. A migration `2026_10_18_200000_add_cycle_idea_decisions.php` acrescenta uma tabela para decisão, responsável, justificativa e datas; não reescreve ideias, votos ou reconhecimentos anteriores. Na base local ela foi aplicada após o backup `storage/app/backups/20261018-201750-MjgPTg1e`.

O resultado da decisão (selecionada ou banco) fica visível no ciclo. As justificativas administrativas ficam restritas à administração no ciclo e aos participantes nas notificações/histórico da própria proposta. A publicação e os votos exibidos aos funcionários não incluem autoria nem documentos privados.
