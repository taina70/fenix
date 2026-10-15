<template>
  <div class="space-y-6 max-w-4xl mx-auto p-6">
    <header class="flex justify-between items-center border-b pb-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">✏️ Editar Prova</h1>
        <p class="text-slate-500 text-sm">Modifique o título, descrição ou as questões da avaliação</p>
      </div>
      <router-link to="/professor/exams" class="text-sm font-semibold text-slate-600 hover:underline">
        Voltar para a listagem
      </router-link>
    </header>

    <!-- Estado de Carregamento -->
    <div v-if="loading" class="text-center p-12 text-slate-500 font-medium bg-white rounded-xl border border-slate-200 animate-pulse">
      Carregando dados da avaliação...
    </div>

    <!-- Formulário de Edição -->
    <form v-else-if="formData" @submit.prevent="handleSave" class="space-y-6">
      <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-4">
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">Título da Prova</label>
          <input v-model="formData.title" type="text" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" required />
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">Descrição</label>
          <textarea v-model="formData.description" rows="3" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
        </div>
      </div>

      <!-- Listagem de Questões -->
      <div class="space-y-4">
        <h2 class="text-lg font-bold text-slate-800">Preguntas e Alternativas</h2>
        <div v-for="(question, qIndex) in formData.questions" :key="qIndex" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-4">
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Enunciado da Questão #{{ qIndex + 1 }}</label>
            <input v-model="question.statement" type="text" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" required />
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Pontos</label>
            <input v-model.number="question.points" type="number" class="w-32 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" required />
          </div>

          <!-- Alternativas -->
          <div class="space-y-2 pt-2 border-t">
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Alternativas de Resposta</label>
            <div v-for="(option, oIndex) in question.options" :key="oIndex" class="flex items-center space-x-3">
              <input 
                type="radio" 
                :name="'correct-' + qIndex" 
                :checked="option.is_correct === true || option.is_correct == 1" 
                @change="setCorrectOption(qIndex, oIndex)"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300" 
              />
              <input v-model="option.text" type="text" class="w-full px-3 py-1.5 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Texto da alternativa" required />
            </div>
          </div>
        </div>
      </div>

      <!-- Botão de Ação -->
      <div class="flex justify-end space-x-3">
        <router-link to="/professor/exams" class="px-4 py-2 border rounded-lg font-medium text-sm text-slate-700 bg-white hover:bg-slate-50 transition">
          Cancelar
        </router-link>
        <button type="submit" :disabled="submitting" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white font-medium text-sm rounded-lg transition">
          {{ submitting ? 'Salvando...' : 'Salvar Alterações' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useExams } from '../../components/useExams';

const route = useRoute();
const router = useRouter();
const { exam, loading, submitting, fetchExamById, updateExam } = useExams();

const formData = ref(null);

onMounted(async () => {
  await fetchExamById(route.params.id);
  if (exam.value) {
    // Clona os dados reativos para o nosso formulário local
    formData.value = JSON.parse(JSON.stringify(exam.value));
  }
});

// Garante que apenas uma alternativa fique marcada como correta na interface
const setCorrectOption = (qIndex, oIndex) => {
  formData.value.questions[qIndex].options.forEach((opt, idx) => {
    // Garante um booleano puro (true se for o índice selecionado, false para os outros)
    opt.is_correct = idx === oIndex;
  });
};

const handleSave = async () => {
  try {
    const cleanPayload = {
      title: formData.value.title,
      description: formData.value.description || null,
      created_by: formData.value.created_by || 1, // Garante o ID do professor exigido pelo backend
      questions: formData.value.questions.map((question) => {
        return {
          statement: question.statement,
          points: Number(question.points), // Garante que envie como número inteiro puro
          options: question.options.map((option) => {
            return {
              text: option.text,
              is_correct: option.is_correct === true || option.is_correct === 1
            };
          })
        };
      })
    };

    // 2. Envia o payload sanitizado para a rota de atualização
    await updateExam(route.params.id, cleanPayload);
    
    alert('Prova atualizada com sucesso!');
    router.push('/professor/exams/manage'); // Redireciona para a tela de gerenciamento
  } catch (err) {
    alert('Erro ao salvar as alterações da prova. Verifique se todas as questões possuem uma resposta correta.');
  }
};


</script>
