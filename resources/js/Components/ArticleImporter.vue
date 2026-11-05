<template>
  <div class="p-6 bg-white rounded-lg shadow-sm border border-gray-200">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Importar Referências (.RIS)</h3>

    <!-- Área de Upload (Visível quando ocioso ou com erro) -->
    <div v-if="status === 'idle' || status === 'error'" class="space-y-4">
      <div 
        class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:bg-gray-50 transition-colors"
        :class="{ 'border-red-400 bg-red-50': status === 'error' }"
      >
        <input 
          type="file" 
          ref="fileInput" 
          accept=".ris" 
          @change="handleFileChange" 
          class="hidden" 
          id="file-upload"
        />
        <label for="file-upload" class="cursor-pointer flex flex-col items-center justify-center">
          <svg class="w-10 h-10 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          <span class="text-sm font-medium text-gray-700">
            {{ file ? file.name : 'Clique para selecionar um arquivo .RIS' }}
          </span>
          <span class="text-xs text-gray-500 mt-1">Tamanho máximo: 10MB</span>
        </label>
      </div>

      <div v-if="errorMsg" class="text-sm text-red-600 font-medium text-center">
        {{ errorMsg }}
      </div>

      <button 
        type="button" 
        @click.prevent="startUpload" 
        :disabled="!file"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Iniciar Importação
      </button>
    </div>

    <!-- Área de Processamento (Visível durante upload e chunking) -->
    <div v-else-if="status === 'uploading' || status === 'processing'" class="space-y-4 py-4">
      <div class="flex justify-between text-sm font-medium text-gray-700 mb-1">
        <span>{{ status === 'uploading' ? 'Enviando arquivo...' : 'Processando lotes e deduplicando...' }}</span>
        <span>{{ Math.round(progress) }}%</span>
      </div>
      
      <!-- Barra de Progresso Animada -->
      <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
        <div 
          class="bg-blue-600 h-2.5 rounded-full transition-all duration-300 ease-out" 
          :style="{ width: progress + '%' }"
        ></div>
      </div>
      
      <p class="text-xs text-gray-500 text-center mt-2" v-if="status === 'processing'">
        Processando {{ stats.current }} de {{ stats.total }} artigos...
      </p>
    </div>

    <!-- Área de Sucesso -->
    <div v-else-if="status === 'completed'" class="text-center py-6 space-y-4">
      <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 mb-2">
        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
      </div>
      <h4 class="text-lg font-medium text-gray-900">Importação Concluída!</h4>
      <div class="bg-gray-50 rounded-md p-4 flex justify-around text-sm border border-gray-100">
        <div class="flex flex-col">
          <span class="text-gray-500">Inseridos</span>
          <span class="font-bold text-green-600 text-xl">{{ stats.inserted }}</span>
        </div>
        <div class="flex flex-col border-l pl-4 border-gray-200">
          <span class="text-gray-500">Duplicatas Ignoradas</span>
          <span class="font-bold text-yellow-600 text-xl">{{ stats.duplicates }}</span>
        </div>
      </div>
      <button @click="resetComponent" class="text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">
        Importar outro arquivo
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  projectId: {
    type: [Number, String],
    required: true
  }
});

// Estados Reativos
const file = ref(null);
const fileInput = ref(null);
const status = ref('idle'); // 'idle', 'uploading', 'processing', 'completed', 'error'
const progress = ref(0);
const errorMsg = ref('');
const tempFileToken = ref('');

const stats = ref({
  total: 0,
  current: 0,
  inserted: 0,
  duplicates: 0,
  batchSize: 200
});

// Captura o arquivo do input
const handleFileChange = (e) => {
  const selectedFile = e.target.files[0];
  if (selectedFile && selectedFile.name.endsWith('.ris')) {
    file.value = selectedFile;
    errorMsg.value = '';
    status.value = 'idle';
  } else {
    file.value = null;
    errorMsg.value = 'Por favor, selecione um arquivo .RIS válido.';
    status.value = 'error';
  }
};

// Reseta o componente para o estado inicial
const resetComponent = () => {
  file.value = null;
  status.value = 'idle';
  progress.value = 0;
  errorMsg.value = '';
  stats.value = { total: 0, current: 0, inserted: 0, duplicates: 0, batchSize: 200 };
  if (fileInput.value) fileInput.value.value = '';
};

// Passo 1: Envia o arquivo e recebe as instruções para os lotes
const startUpload = async () => {
  if (!file.value) return;

  status.value = 'uploading';
  progress.value = 10; // Progresso visual inicial
  
  const formData = new FormData();
  formData.append('bibliography_file', file.value);

  try {
    const response = await axios.post(`/projects/${props.projectId}/import/upload`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    // Atualiza os dados para iniciar os lotes
    tempFileToken.value = response.data.temp_file;
    stats.value.total = response.data.total_articles;
    stats.value.batchSize = response.data.batch_size;
    
    status.value = 'processing';
    progress.value = 20; // Avança visualmente ao entrar na fase de chunks
    
    // Inicia a função recursiva chamando a primeira página
    processBatch(1);

  } catch (error) {
    status.value = 'error';
    errorMsg.value = error.response?.data?.error || 'Erro ao fazer upload do arquivo.';
  }
};

// Passo 2: A função recursiva "Maestro" que manda os lotes para o backend
const processBatch = async (page) => {
  try {
    const response = await axios.post(`/projects/${props.projectId}/import/process`, {
      temp_file: tempFileToken.value,
      page: page,
      batch_size: stats.value.batchSize
    });

    // 1. Sempre atualiza as estatísticas PRIMEIRO (se o backend enviou os dados)
    if (response.data.inserted !== undefined) {
      stats.value.inserted += response.data.inserted;
      stats.value.duplicates += response.data.duplicates;
      stats.value.current = Math.min(page * stats.value.batchSize, stats.value.total);
    }

    // 2. Checa se o backend avisou que o processo terminou
    if (response.data.status === 'completed') {
      progress.value = 100;
      
      setTimeout(() => { 
        status.value = 'completed'; 
        
        // Pede ao Inertia para buscar os dados atualizados do projeto no backend
        router.reload({ only: ['project'] });
        
      }, 500);
      
      return; // Encerra a recursividade
    }

    // 3. Calcula o progresso visual (20% a 100%) se ainda estiver processando
    const processedRatio = stats.value.current / stats.value.total;
    progress.value = 20 + (processedRatio * 80);

    // 4. Chama o próximo lote de forma recursiva
    processBatch(page + 1);

  } catch (error) {
    status.value = 'error';
    const serverError = error.response?.data?.message || error.response?.data?.error || error.message;
    errorMsg.value = `Erro no Lote ${page}: ${serverError}`;
    console.error("Detalhes Completos do Erro Backend:", error.response?.data);
  }
};
</script>