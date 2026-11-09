<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">Codificação: {{ project.title }}</h2>
          <p class="text-sm text-gray-500 mt-1">
            Artigos Codificados: {{ stats.total_coded }} de {{ stats.total_included }}
          </p>
        </div>
        <Link :href="route('projects.show', project.id)" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
          &larr; Voltar ao Projeto
        </Link>
      </div>
    </template>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8">
      
      <div v-if="articles.data.length > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- COLUNA ESQUERDA: Área de Leitura (Ocupa 2 espaços) -->
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
            <!-- Paginação Simples no topo -->
            <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
              <span class="text-sm font-medium text-gray-500">
                Artigo {{ articles.current_page }} de {{ articles.last_page }}
              </span>
              <div class="flex gap-2">
                <Link v-if="articles.prev_page_url" :href="articles.prev_page_url" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm transition">&larr; Anterior</Link>
                <Link v-if="articles.next_page_url" :href="articles.next_page_url" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm transition">Próximo &rarr;</Link>
              </div>
            </div>

            <!-- Dados do Artigo -->
            <h1 class="text-2xl font-bold text-gray-900 leading-tight mb-3">
              {{ currentArticle.title }}
            </h1>
            <p class="text-sm text-indigo-600 font-medium mb-6">
              {{ currentArticle.authors }} <span v-if="currentArticle.publication_year">({{ currentArticle.publication_year }})</span>
            </p>
            
            <div class="prose max-w-none text-gray-700 text-base leading-relaxed bg-gray-50 p-6 rounded-md border border-gray-100">
              <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-2">Resumo (Abstract)</h4>
              <p v-if="currentArticle.abstract">{{ currentArticle.abstract }}</p>
              <p v-else class="italic text-gray-400">Nenhum resumo disponível.</p>
            </div>
          </div>
        </div>

        <!-- COLUNA DIREITA: Codebook e Tags -->
        <div class="lg:col-span-1 space-y-6">
          
          <!-- Formulário para Criar Nova Tag -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <h3 class="text-md font-bold text-gray-900 mb-3">Criar Novo Código</h3>
            <form @submit.prevent="createTag" class="flex gap-2">
              <input 
                v-model="newTagForm.name" 
                type="text" 
                placeholder="Ex: Metodologia Qualitativa" 
                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                required
              />
              <input type="color" v-model="newTagForm.color" class="h-9 w-9 rounded border-gray-300 cursor-pointer" title="Cor da Tag">
              <button type="submit" :disabled="newTagForm.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-md text-sm font-medium transition">
                +
              </button>
            </form>
          </div>

          <!-- Lista de Tags (Codebook) -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <h3 class="text-md font-bold text-gray-900 mb-4">Aplicar Códigos</h3>
            
            <div v-if="codebook.length === 0" class="text-sm text-gray-500 italic text-center py-4">
              Seu codebook está vazio. Crie uma tag acima.
            </div>

            <div v-else class="flex flex-wrap gap-2">
              <!-- Botões de Toggle para cada Tag -->
              <button 
                v-for="tag in codebook" 
                :key="tag.id"
                @click="toggleTag(tag.id)"
                :class="[
                  'px-3 py-1.5 text-sm font-medium rounded-full transition-all border-2',
                  isTagApplied(tag.id) 
                    ? 'shadow-sm text-white' 
                    : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'
                ]"
                :style="isTagApplied(tag.id) ? { backgroundColor: tag.color, borderColor: tag.color } : {}"
              >
                {{ isTagApplied(tag.id) ? '✓ ' : '+ ' }} {{ tag.name }}
              </button>
            </div>
          </div>

        </div>

      </div>

      <!-- Estado Vazio -->
      <div v-else class="text-center py-16 bg-white rounded-lg shadow-sm border border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Nenhum artigo aceito ainda.</h3>
        <p class="text-gray-500 mt-2">Você precisa aprovar (incluir) artigos na etapa de Triagem antes de começar a codificar.</p>
        <Link :href="route('projects.screening', project.id)" class="mt-4 inline-block text-indigo-600 font-medium hover:underline">
          Ir para a Triagem &rarr;
        </Link>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  project: Object,
  articles: Object,
  codebook: Array,
  stats: Object,
});

// Artigo atual em exibição (paginado como 1)
const currentArticle = computed(() => props.articles.data[0]);

// Formulário de nova Tag
const newTagForm = useForm({
  name: '',
  color: '#4F46E5', // Cor índigo padrão
});

const createTag = () => {
  newTagForm.post(route('projects.tags.store', props.project.id), {
    preserveScroll: true,
    onSuccess: () => newTagForm.reset('name'),
  });
};

// Verifica se a Tag específica está presente no array de tags do Artigo Atual
const isTagApplied = (tagId) => {
  if (!currentArticle.value || !currentArticle.value.tags) return false;
  return currentArticle.value.tags.some(t => t.id === tagId);
};

// Dispara a requisição para plugar/desplugar a tag do artigo
const toggleTag = (tagId) => {
  router.post(route('articles.tags.toggle', currentArticle.value.id), {
    tag_id: tagId
  }, {
    preserveScroll: true,
    preserveState: true, // Mantém o usuário na mesma página sem piscar a tela
  });
};
</script>