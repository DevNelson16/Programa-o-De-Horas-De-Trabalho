# ProgHours - Sistema de Controlo de Horas de Programacao

## Requisitos

- **XAMPP** (ou similar: WAMP, MAMP, LAMP)
  - PHP 7.4 ou superior (recomendado: PHP 8.x)
  - MySQL 5.7 ou superior / MariaDB 10.x
  - Apache com mod_rewrite

---

## Instalacao no XAMPP

### Passo 1: Copiar os ficheiros

1. Copia a pasta `programacao-horas` para dentro de `C:\xampp\htdocs\`
2. A estrutura deve ficar assim:
   ```
   C:\xampp\htdocs\programacao-horas\
   ```

### Passo 2: Configurar a Base de Dados

1. Abre o **XAMPP Control Panel**
2. Inicia o **Apache** e o **MySQL**
3. Clica em **Admin** do MySQL para abrir o phpMyAdmin
4. No phpMyAdmin:
   - Clica em **"SQL"** no menu superior
   - Copia e cola o conteudo do ficheiro `database/schema.sql`
   - Clica em **"Go"** ou **"Executar"**

> Alternativa: Importa o ficheiro `database/schema.sql` diretamente atraves da aba **"Import"** do phpMyAdmin.

### Passo 3: Configurar o PHP (se necessario)

- Verifica se as extensoes PDO e PDO_MySQL estao ativas no `php.ini`:
  ```ini
  extension=pdo_mysql
  ```
- Ja vem ativado por padrao no XAMPP.

### Passo 4: Aceder ao Sistema

1. Abre o navegador
2. Acede a: `http://localhost/programacao-horas/public/`
3. Vais ser redirecionado para a pagina de login

---

## Contas de Demonstracao

| Email | Password | Tipo |
|-------|----------|------|
| admin@sistema.pt | admin123 | Administrador |
| user@sistema.pt | user123 | Utilizador |
| esposa@sistema.pt | user123 | Utilizador |

---

## Testar no Telemovel (Mesma Rede Wi-Fi)

### 1. Descobrir o IP do computador

**Windows:**
- Abre o **Prompt de Comando** (cmd)
- Escreve: `ipconfig`
- Procura por **"IPv4 Address"** (algo como `192.168.1.XXX`)

**Mac/Linux:**
- Abre o **Terminal**
- Escreve: `ifconfig` ou `ip addr`
- Procura pelo IP da rede local (algo como `192.168.1.XXX`)

### 2. Aceder pelo telemovel

1. Liga o telemovel a **mesma rede Wi-Fi** do computador
2. Abre o navegador do telemovel
3. Acede a:
   ```
   http://192.168.1.XXX/programacao-horas/public/
   ```
   (substitui `192.168.1.XXX` pelo teu IP real)

### 3. Se nao funcionar (firewall)

**Windows:**
- Abre o **Windows Defender Firewall**
- Clica em **"Allow an app or feature through Windows Firewall"**
- Adiciona o **Apache HTTP Server** ou desativa temporariamente o firewall para testar

---

## Estrutura de Pastas

```
programacao-horas/
|-- database/              # Scripts SQL
|   |-- schema.sql         # Criacao das tabelas e dados iniciais
|
|-- includes/              # Ficheiros PHP reutilizaveis
|   |-- config.php         # Configuracoes da app
|   |-- db.php             # Conexao PDO + funcoes CRUD
|   |-- auth.php           # Autenticacao e sessoes
|   |-- functions.php      # Funcoes auxiliares
|   |-- header.php         # Header comum (HTML + sidebar)
|   |-- footer.php         # Footer comum (fecha HTML)
|
|-- public/                # Pasta publica (acessivel pelo browser)
|   |-- index.php          # Dashboard do utilizador
|   |-- login.php          # Pagina de login
|   |-- logout.php         # Terminar sessao
|   |-- timer.php          # Temporizador de horas
|   |-- historico.php      # Historico de horas
|   |-- relatorios.php     # Enviar/ver relatorios
|   |-- perfil.php         # Perfil do utilizador
|   |
|   |-- api/               # API endpoints
|   |   |-- temporizador.php  # API do temporizador
|   |
|   |-- admin/             # Area de administracao
|   |   |-- dashboard.php  # Admin dashboard
|   |   |-- utilizadores.php # Gestao de utilizadores
|   |   |-- horas.php      # Gestao de todas as horas
|   |   |-- relatorios.php # Gestao de todos os relatorios
|   |
|   |-- assets/            # Recursos estaticos
|       |-- css/
|       |   |-- style.css  # Estilos principais
|       |-- js/
|           |-- main.js    # JavaScript principal
|           |-- timer.js   # Logica do temporizador
|
|-- INSTALL.md             # Este ficheiro
```

---

## Funcionalidades

### Utilizador
- Login seguro com sessoes
- Temporizador de horas (iniciar, pausar, terminar)
- Historico de horas por dia/semana/mes
- Envio de relatorios de estudo
- Perfil pessoal com estatisticas

### Administrador
- Dashboard com estatisticas globais
- Gestao completa de utilizadores (CRUD)
- Edicao e remocao de registos de horas
- Gestao completa de relatorios
- Graficos de horas por dia e por mes

### Seguranca
- Prepared statements (PDO) contra SQL Injection
- Passwords com bcrypt/hash
- Protecao CSRF
- Sessoes seguras (httponly)
- Controlo de acesso por roles (admin/user)

---

## Melhorias Futuras Sugeridas

1. **Exportacao PDF** - Integrar biblioteca como TCPDF ou dompdf para exportar relatorios
2. **Notificacoes** - Alertas quando se atinge metas de horas semanais
3. **Metas/Objectivos** - Definir metas de horas por semana/mes
4. **Tema Escuro** - Toggle para modo escuro/claro
5. **API REST completa** - Para integracao com outras ferramentas
6. **Backup automatico** - Exportacao da base de dados periodicamente
7. **Categorias** - Classificar horas por tipo (PHP, JS, CSS, etc.)
8. **Gamificacao** - Niveis/badges por horas cumpridas
9. **Multi-idioma** - Suporte para PT, EN, ES
10. **Docker** - Containerizar para deploy facil

---

## Resolucao de Problemas

### Erro de conexao a base de dados
- Verifica se o MySQL esta iniciado no XAMPP
- Verifica as credenciais em `includes/config.php`
- Por padrao: user=`root`, password=` ` (vazio)

### Pagina em branco
- Ativa o display de erros no `includes/config.php`:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```
- Verifica os logs do Apache em `C:\xampp\apache\logs\error.log`

### CSS/JS nao carrega
- Verifica se a constante `APP_URL` em `config.php` esta correta
- Se usares outra pasta, ajusta: `define('APP_URL', 'http://localhost/nome-da-pasta');`

---

## Tecnologias Utilizadas

- **PHP 8.x** - Backend
- **MySQL** - Base de dados
- **PDO** - Acesso a dados com prepared statements
- **HTML5** - Estrutura
- **CSS3** - Estilos modernos com variaveis CSS
- **JavaScript Vanilla** - Interatividade
- **Chart.js** - Graficos
- **Font Awesome** - Icones
- **Inter** - Tipografia (Google Fonts)
