<template>
  <div id="app" class="app-container">
    <header class="navbar">
      <div class="logo">🦅 Plataforma Fênix</div>
      <nav class="nav-links">
        <div class="profile-group">
          <span class="role-label">👨‍🎓 Aluno:</span>
          <router-link to="/student/exams">Provas Disponíveis</router-link>
        </div>
        <div class="profile-group">
          <span class="role-label">👨‍🏫 Professor:</span>
          <router-link to="/professor/dashboard">Dashboard</router-link>
          <router-link to="/professor/exams/create">Nova Prova</router-link>
          <router-link to="/professor/exams/manage">Gerenciar Provas</router-link>
        </div>
      </nav>
    </header>

    <main class="content">
      <router-view />
    </main>
  </div>
</template>

<style scoped>
.app-container { font-family: system-ui, -apple-system, sans-serif; color: #333; }
.navbar { display: flex; justify-content: space-between; align-items: center; padding: 1rem 2rem; background: #1e293b; color: white; }
.logo { font-size: 1.25rem; font-weight: bold; }
.nav-links { display: flex; gap: 1.5rem; }
.profile-group { display: flex; align-items: center; gap: 0.5rem; background: #334155; padding: 0.4rem 0.8rem; border-radius: 6px; }
.role-label { font-size: 0.85rem; color: #94a3b8; font-weight: bold; }
a { color: #f8fafc; text-decoration: none; font-size: 0.9rem; }
a.router-link-active { color: #38bdf8; font-weight: bold; }
.content { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
</style>