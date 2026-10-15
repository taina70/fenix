<template>
  <div>
    <h2>📝 Provas Disponíveis</h2>
    <div v-if="loading">Carregando provas...</div>
    <div v-else-if="exams.length === 0">Nenhuma prova cadastrada no momento.</div>
    
    <div v-else class="exam-grid">
      <div v-for="exam in exams" :key="exam.id" class="exam-card">
        <h3>{{ exam.title }}</h3>
        <p>{{ exam.description || 'Sem descrição.' }}</p>
        <router-link :to="`/student/exams/${exam.id}/take`" class="btn">
          Iniciar Prova
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api/axios';

const exams = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const response = await api.get('/exams');
    exams.value = response.data;
  } catch (error) {
    alert('Erro ao carregar lista de provas.');
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.exam-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; margin-top: 1rem; }
.exam-card { border: 1px solid #e2e8f0; padding: 1.5rem; border-radius: 8px; background: #fff; }
.btn { display: inline-block; margin-top: 1rem; background: #2563eb; color: white; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; }
</style>