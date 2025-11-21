# Dashboard Backend – Grupo Six  
**Teste Técnico – Desenvolvedor Backend Pleno**

Este projeto foi desenvolvido como solução para o teste técnico do **Grupo Six**, cujo objetivo é implementar um **dashboard completo** consumindo dados de uma API externa, processando métricas relevantes e exibindo essas análises em uma interface utilizando **Laravel + Blade + Bootstrap**.

O foco principal foi demonstrar domínio de:
- Construção de serviços backend
- Tratamento, agregação e análise de dados
- Integração com APIs externas
- Organização de código e boas práticas
- Apresentação clara de métricas e KPIs  
- Arquitetura limpa e extensível

---

## 🧠 Contexto do Desafio

O Grupo Six opera com produtos físicos e utiliza o gateway internacional **Cartpanda**. O backend (CRM interno) consolida dados de diversas fontes, e o desafio consiste em:

> Criar um painel analítico com métricas importantes sobre pedidos, produtos e clientes utilizando uma API fornecida pelo time.

Endpoint disponibilizado:

```
https://dev-crm.ogruposix.com/candidato-teste-pratico-backend-dashboard/test-orders

```

---

# 🚀 Tecnologias utilizadas

- **PHP 8+**
- **Laravel 10**
- **Bootstrap 5** (UI)
- **Blade** (View engine)
- **Cache com Laravel Cache** (evita reprocessamento)
- **Chart.js** (Gráfico Top 5 produtos)

---

# 📂 Arquitetura e Organização

### app/Services/OrderMetricsService.php
Responsável por:
- Consumir a API externa
- Normalizar e tratar os dados
- Calcular todas as métricas
- Montar agrupamentos e somatórios
- Padronizar valores financeiros

Toda lógica de negócio está centralizada aqui.

---

### app/Http/Controllers/DashboardController.php
Funções:
- Receber requisição
- Aplicar busca e paginação
- Passar métricas e tabelas para a view
- Controlar o fluxo entre Service - View

---

### resources/views/dashboard/index.blade.php
A camada visual do projeto:
- Cards de KPIs
- Tabelas dinâmicas
- Gráfico Top 5 Produtos
- Tabela de pedidos com paginação
- Listagens e rankings
- Interface responsiva com Bootstrap 5

---

### routes/web.php
Rotas do sistema:

```php
Route::get('/', fn() => redirect()->route('dashboard.index'));
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
```

---

# Como rodar o projeto

1. Instale dependências:
```bash
composer install
```

2. Configure o `.env`:
```env
SIX_API_ENDPOINT=https://dev-crm.ogruposix.com/candidato-teste-pratico-backend-dashboard/test-orders
```

3. Adicione em `config/services.php`:
```php
'six' => [
    'endpoint' => env('SIX_API_ENDPOINT'),
],
```

4. Limpe caches do Laravel:
```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

5. Rode o servidor:
```bash
php artisan serve
```

6. Acesse:
```
http://localhost:8000/dashboard
```

---

# Conclusão

Este projeto demonstra domínio em:
- Laravel
- Manipulação de coleções
- Organização modular de código
- Construção de dashboards
- Consumo e padronização de APIs externas
