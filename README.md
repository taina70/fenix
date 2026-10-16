# 📝 Platform Exam & Assessment

Plataforma web de avaliações online desenvolvida com Laravel, Vue.js e PostgreSQL. Permite que professores gerenciem provas e que alunos respondam às avaliações, consultem suas notas e acompanhem seu histórico de desempenho.

O projeto utiliza Docker Compose para executar os serviços, Redis para cache das métricas do dashboard e OpenAPI/Swagger para documentar a API REST.

## 🚀 Tecnologias

### Backend
- **PHP 8.4** — linguagem principal.
- **Laravel 11** — framework para a API REST.
- **PostgreSQL 16** — banco de dados relacional.
- **Redis** — cache das métricas do dashboard.
- **L5-Swagger / OpenAPI 3.0** — documentação interativa da API.
- **PHPUnit** — testes automatizados.

### Frontend
- **Vue 3** — construção da interface.
- **JavaScript** — lógica e integração com a API.
- **Axios** — comunicação HTTP com o backend.
- **Tailwind CSS** — estilização da interface.

### Infraestrutura
- **Docker**
- **Docker Compose**

## ✨ Funcionalidades

### 👩‍🏫 Área do professor
- Criar, editar e excluir provas.
- Cadastrar questões de múltipla escolha e suas alternativas.
- Definir a alternativa correta de cada questão.
- Validar os dados das avaliações antes de salvá-las.
- Consultar métricas e ranking de desempenho no dashboard.

### 🎓 Área do aluno
- Consultar provas disponíveis.
- Responder às questões e enviar as respostas.
- Receber correção automática e visualizar a pontuação.
- Consultar o percentual de aproveitamento.
- Visualizar o histórico de avaliações concluídas.

O projeto utiliza perfis simulados de aluno e professor para demonstrar os fluxos da aplicação, sem implementar um sistema completo de autenticação.

### 📊 Dashboard
- Média de desempenho.
- Maior e menor pontuação.
- Total de tentativas registradas.
- Ranking de alunos.
- Cache das métricas para reduzir consultas repetidas.

## 🧩 Regras de negócio

- Cada questão deve possuir pelo menos duas alternativas.
- Cada questão deve ter exatamente uma alternativa correta.
- O sistema valida as alternativas enviadas antes de corrigir uma prova.
- A pontuação é calculada automaticamente com base nas respostas.
- Uma tentativa por aluno e prova é permitida.
- O sistema trata submissões concorrentes para evitar tentativas duplicadas.
- O gabarito não é exposto na consulta de detalhes da prova.
- O histórico registra a prova realizada, a pontuação e o percentual de aproveitamento.

## 🏗️ Arquitetura e boas práticas

- **API REST:** comunicação entre frontend e backend por meio de JSON.
- **Service Pattern:** centralização de regras de negócio, cálculos e métricas no `ExamService`.
- **Validação de dados:** respostas apropriadas para entradas inválidas e recursos inexistentes.
- **Integridade de dados:** relacionamentos entre provas, questões, alternativas, alunos e tentativas.
- **Cache:** utilização do Redis para armazenar métricas do dashboard.
- **Testes automatizados:** validação de endpoints, regras de negócio, relacionamentos e cenários de concorrência.
- **Documentação da API:** especificação OpenAPI com interface Swagger.

## 🛠️ Como executar o projeto

### Pré-requisitos

- Git
- Docker Engine
- Docker Compose

### 1. Clonar o repositório

Substitua a URL pelo endereço do repositório:

```bash
git clone <URL_DO_REPOSITORIO>
cd <PASTA_DO_PROJETO>
```

### 2. Configurar o ambiente

```bash
cp backend/.env.example backend/.env
```

Confira as variáveis de ambiente do Laravel para garantir que o banco de dados esteja configurado para o serviço PostgreSQL do Docker Compose.

### 3. Iniciar os containers

```bash
docker compose up -d --build
```

### 4. Executar as migrações

```bash
docker compose exec backend php artisan migrate --seed
```

Esse comando aplica as migrações e executa os seeds. Caso o banco já contenha dados, evite utilizar `migrate:fresh`, pois esse comando apaga as tabelas antes de recriá-las.

### 5. Gerar a documentação da API

```bash
docker compose exec backend php artisan l5-swagger:generate
```

Acesse a documentação interativa em:

[http://localhost:8000/api/documentation](http://localhost:8000/api/documentation)

### 6. Executar os testes

```bash
docker compose exec backend php artisan test
```

Para executar os testes com relatório de cobertura, utilizando o Xdebug:

```bash
docker compose exec -e XDEBUG_MODE=coverage backend php artisan test --coverage --coverage-text
```

**Resultado de referência da última execução:** 32 testes aprovados, 97 asserções e 88,5% de cobertura de código. Os números podem mudar conforme novos testes forem adicionados.

## 📚 Principais endpoints

| Método | Endpoint | Descrição |
|---|---|---|
| `GET` | `/api/exams` | Lista as provas disponíveis. |
| `GET` | `/api/exams/{id}` | Consulta os detalhes de uma prova sem expor o gabarito. |
| `POST` | `/api/exams` | Cria uma prova. |
| `PUT` | `/api/exams/{id}` | Atualiza uma prova. |
| `DELETE` | `/api/exams/{id}` | Exclui uma prova. |
| `POST` | `/api/exams/{id}/submit` | Envia respostas e registra a tentativa. |
| `GET` | `/api/dashboard` | Consulta métricas e ranking. |
| `GET` | `/api/students/{student}/attempts` | Consulta o histórico de provas de um aluno. |

A documentação Swagger apresenta os detalhes dos parâmetros, corpos das requisições e respostas documentadas da API.

## 🧪 Estratégia de testes

A suíte automatizada contempla cenários como:

- Validação na criação e atualização de provas.
- Rejeição de questões com quantidade inválida de alternativas corretas.
- Correção automática de respostas.
- Rejeição de submissões duplicadas.
- Tratamento de submissões concorrentes.
- Proteção do gabarito nas consultas.
- Cálculo das métricas e do ranking.
- Relacionamentos entre modelos e integridade dos dados.
- Consulta do histórico de avaliações.

A cobertura de referência é de **88,5%**, a

## 🖥️ Interface

A aplicação possui telas para os dois fluxos simulados:

- **Aluno:** consulta de provas pendentes, avaliações concluídas, notas e histórico.
- **Professor:** gerenciamento de avaliações e acompanhamento dos resultados.
- **Dashboard:** visualização das métricas e do ranking de desempenho.
