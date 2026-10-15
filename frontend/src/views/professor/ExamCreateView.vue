<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <header class="flex justify-between items-center border-b border-slate-200 pb-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">➕ Cadastrar Nova Prova</h1>
        <p class="text-slate-500 text-sm">Monte o cabeçalho, adicione questões e defina o gabarito</p>
      </div>
    </header>

    <form @submit.prevent="handleSave" class="space-y-6">
      <!-- Informações Gerais -->
      <div class="bg-white p-6 rounded-xl border border-slate-200/80 shadow-sm space-y-4">
        <h2 class="text-lg font-semibold text-slate-800 border-b border-slate-100 pb-2">Informações Gerais</h2>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Título da Prova *</label>
          <input 
            type="text" 
            v-model="form.title" 
            required 
            placeholder="Ex: Avaliação Intermediária de Algoritmos" 
            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Descrição</label>
          <textarea 
            v-model="form.description" 
            rows="2" 
            placeholder="Ex: Prova contendo conceitos básicos e estruturas condicionais" 
            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition"
          ></textarea>
        </div>
      </div>

      <!-- Seção de Questões -->
      <div class="space-y-4">
        <div class="flex justify-between items-center">
          <h2 class="text-lg font-semibold text-slate-800">Questões ({{ form.questions.length }})</h2>
          <button 
            type="button" 
            @click="addQuestion" 
            class="inline-flex items-center gap-1 bg-slate-800 hover:bg-slate-900 text-white font-medium text-sm px-4 py-2 rounded-lg transition"
          >
            + Adicionar Questão
          </button>
        </div>

        <!-- Renderização do Componente Filho Isolar Questão -->
        <QuestionForm 
          v-for="(question, qIndex) in form.questions" 
          :key="question.id" 
          :question="question" 
          :index="qIndex" 
          :can-remove="form.questions.length > 1" 
          @remove="removeQuestion(qIndex)" 
        />
      </div>

      <div class="pt-4">
        <button 
          type="submit" 
          :disabled="submitting" 
          class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-6 rounded-xl shadow-md transition disabled:opacity-50"
        >
          {{ submitting ? 'Publicando Prova...' : 'Salvar e Publicar Prova' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useExams } from '../../components/useExams';
import QuestionForm from '../../components/professor/QuestionForm.vue';

const router = useRouter();
const { createExam, submitting } = useExams();

const createNewQuestion = () => ({
  id: crypto.randomUUID(),
  statement: '',
  points: 10,
  options: [
    { id: crypto.randomUUID(), text: '', is_correct: true },
    { id: crypto.randomUUID(), text: '', is_correct: false }
  ]
});

const form = reactive({
  title: '',
  description: '',
  created_by: 1,
  questions: [createNewQuestion()]
});

const addQuestion = () => {
  form.questions.push(createNewQuestion());
};

const removeQuestion = (index) => {
  form.questions.splice(index, 1);
};

const handleSave = async () => {
  const payload = {
    ...form,
    questions: form.questions.map(({ id, options, ...q }) => ({
      ...q,
      options: options.map(({ id: optId, ...opt }) => opt)
    }))
  };

  try {
    await createExam(payload);
    alert('Prova criada com sucesso!');
    router.push('/professor/exams/manage');
  } catch (err) {
    alert(err.response?.data?.message || 'Erro ao salvar a prova.');
  }
};
</script>