<template>
  <div id="app" class="app-container">
    <header class="navbar">
      <div class="logo">🦅 Plataforma Fênix</div>

      <!-- Seletor de Perfil Simples -->
      <div class="user-switcher">
        <button 
          @click="changeRole('student')" 
          :class="['role-btn', { active: isStudent }]"
        >
          👨‍🎓 Perfil Aluno
        </button>
        <button 
          @click="changeRole('teacher')" 
          :class="['role-btn', { active: isTeacher }]"
        >
          👨‍🏫 Perfil Professor
        </button>
      </div>

      <nav class="nav-links">
        <!-- Links Visíveis Apenas para o Aluno -->
        <div v-if="isStudent" class="profile-group">
          <span class="role-label">Área do Aluno:</span>
          <router-link to="/student/exams">Provas Disponíveis</router-link>
        </div>

        <!-- Links Visíveis Apenas para o Professor -->
        <div v-if="isTeacher" class="profile-group">
          <span class="role-label">Área do Professor:</span>
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

<script setup>
import { useRouter } from 'vue-router';
import { useAuth } from './components/useAuth'; // Ajuste o caminho se necessário

const router = useRouter();
const { isStudent, isTeacher, switchUser } = useAuth();

const changeRole = (role) => {
  switchUser(role);

  if (role === 'teacher') {
    router.push('/professor/dashboard');
  } else {
    router.push('/student/exams');
  }
};
</script>

<style scoped>
.app-container { font-family: system-ui, -apple-system, sans-serif; color: #333; }
.navbar { display: flex; justify-content: space-between; align-items: center; padding: 1rem 2rem; background: #1e293b; color: white; }
.logo { font-size: 1.25rem; font-weight: bold; }
.nav-links { display: flex; gap: 1.5rem; }
.profile-group { display: flex; align-items: center; gap: 0.5rem; background: #334155; padding: 0.4rem 0.8rem; border-radius: 6px; }
.role-label { font-size: 0.85rem; color: #94a3b8; font-weight: bold; }

/* Botões de Troca de Perfil */
.user-switcher { display: flex; gap: 0.5rem; background: #0f172a; padding: 0.3rem; border-radius: 8px; }
.role-btn { background: transparent; color: #94a3b8; border: none; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.role-btn.active { background: #38bdf8; color: #0f172a; }

a { color: #f8fafc; text-decoration: none; font-size: 0.9rem; }
a.router-link-active { color: #38bdf8; font-weight: bold; }
.content { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
</style>