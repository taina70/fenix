# 📝 Platform Exam & Assessment API

Plataforma completa de avaliação e gestão de exames técnicos construída com **Laravel 11 (PHP 8.4)** e **Vue 3**, totalmente containerizada via **Docker Compose**, com suporte a **Redis Cache** e documentada via **OpenAPI / Swagger**.

---

## 🚀 Tecnologias e Arquitetura

### **Backend & Infraestrutura**
* **PHP 8.4** + **Laravel 11**
* **PostgreSQL 16 (Alpine)** - Banco de dados relacional principal.
* **Redis** - Camada de armazenamento em cache para métricas de performance (Dashboard).
* **Docker & Docker Compose** - Containerização completa e orquestração do ambiente.
* **L5-Swagger / OpenAPI 3.0** - Documentação e especificação interativa das rotas de API.
* **PHPUnit** - Suíte automatizada de testes de integração e regras de negócio.

### **Padrões de Projeto & Boas Práticas**
* **Service Pattern (`ExamService`):** Desacoplamento de regras complexas (ex: cálculo de pontuações, verificação de submissão duplicada e métricas) da camada HTTP.
* **API First:** Respostas estruturadas em JSON com tratamento nativo para erros de validação (`422 Unprocessable Entity`) e recursos não encontrados (`404 Not Found`).
* **Regras de Domínio:** Mínimo de 2 opções por questão de múltipla escolha para integridade pedagógica.

---

## 🛠️ Como Executar o Projeto

### **Pré-requisitos**
* Docker Engine & Docker Compose instalados.
* Git.

### **1. Clonar o Repositório**
```bash
git clone <URL_DO_SEU_REPOSITORIO>
cd <NOME_DA_PASTA_DO_PROJETO>

Configurar Variáveis de Ambiente
cp backend/.env.example backend/.env

Subir os Containers Docker
docker compose up -d --build

Rodar Migrações e Seeds do Banco
docker compose exec backend php artisan migrate:fresh --seed

1 - Gere os arquivos de especificação dentro do container 
docker compose exec backend php artisan l5-swagger:generate
2 - Acesse no navegador:
http://localhost:8000/api/documentation
3 -

Executando os Testes Automatizados
docker compose exec backend php artisan test

Rotas da API e Exemplos
Lista todas as provas cadastradas GET /api/exams
Cadastra uma nova prova (mínimo de 2 opções por questão) POST  /api/exams
Exibe detalhes de uma prova (esconde respostas corretas para o aluno) GET /api/exams/{id}
Remove uma prova cadastrada DELETE  /api/exams/{id}
Submete gabarito de um aluno e calcula a nota automaticamente POST  /api/exams/{id}/submit
Retorna estatísticas gerais e ranking de notas (Cached via Redis) GET /api/dashboard


