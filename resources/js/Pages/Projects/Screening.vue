<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">Triagem: {{ project.title }}</h2>
          <div class="flex items-center gap-4 mt-2 text-sm">
            <span class="text-gray-500">Pendentes: <strong class="text-gray-900">{{ stats.pending }}</strong></span>
            <span class="text-green-600">Incluídos: <strong>{{ stats.included }}</strong></span>
            <span class="text-red-600">Excluídos: <strong>{{ stats.excluded }}</strong></span>
          </div>
        </div>
        <Link :href="route('projects.show', project.id)" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
          &larr; Voltar ao Projeto
        </Link>
      </div>
    </template>

    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8">
      
      <!-- Abas de Filtro -->
      <div class="flex space-x-1 bg-gray-100 p-1 rounded-lg mb-6 w-fit">
        <button v-for="tab in tabs" :key="tab.value" @click="changeFilter(tab.value)"
          :class="['px-4 py-2 text-sm font-medium rounded-md transition-colors', 
            currentFilter === tab.value ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
          {{ tab.label }}
        </button>
      </div>

      <!-- Lista de Artigos -->
      <div v-if="articles.data.length > 0" class="space-y-6">
        <div v-for="(article, index) in articles.data" :key="article.id" 
             class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:border-blue-300 transition-colors"
             :class="{'ring-2 ring-blue-500': activeIndex === index}">
          
          <div class="p-6">
            <div class="flex justify-between items-start gap-4">
              <h3 class="text-lg font-bold text-gray-900 leading-snug">{{ article.title }}</h3>
              
              <!-- Badges de Status -->
              <span v-if="article.status === 'included'" class="px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full uppercase shrink-0">Incluído</span>
              <span v-if="article.status === 'excluded'" class="px-3 py-1 bg-red-100 text-red-800 text-xs font-bold rounded-full uppercase shrink-0">Excluído</span>
            </div>

            <p class="text-sm text-gray-500 mt-2 font-medium">
              {{ article.authors || 'Autores desconhecidos' }} 
              <span v-if="article.publication_year" class="text-gray-400">({{ article.publication_year }})</span>
            </p>

            <div class="mt-4 text-gray-700 text-sm leading-relaxed bg-gray-50 p-4 rounded-md border border-gray-100">
              <p v-if="article.abstract">{{ article.abstract }}</p>
              <p v-else class="italic text-gray-400">Resumo não disponível neste registro.</p>
            </div>
          </div>

          <!-- Ações -->
          <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex justify-end gap-3">
            <button @click="setStatus(article.id, 'excluded')" class="px-4 py-2 bg-white border border-gray-300 text-red-600 rounded-md text-sm font-medium hover:bg-red-50 focus:ring-2 focus:ring-red-500 transition">
              ✗ Excluir (←)
            </button>
            <button @click="setStatus(article.id, 'included')" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 transition">
              ✓ Incluir (→)
            </button>
          </div>
        </div>
      </div>

      <!-- Vazio -->
      <div v-else class="text-center py-12 bg-white rounded-lg border border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Nenhum artigo encontrado.</h3>
        <p class="text-gray-500 mt-1">Todos os artigos deste filtro já foram avaliados ou a busca está vazia.</p>
      </div>

      <!-- Paginação do Laravel -->
      <div class="mt-8 flex justify-center" v-if="articles.links.length > 3">
        <div class="flex gap-1">
          <Link v-for="(link, i) in articles.links" :key="i" :href="link.url"
            v-html="link.label"
            class="px-4 py-2 text-sm border rounded-md"
            :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 hover:bg-gray-50 border-gray-300'"
            :disabled="!link.url" />
        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  project: Object,
  articles: Object,
  stats: Object,
  currentFilter: String,
});

const tabs = [
  { label: 'Pendentes', value: 'pending' },
  { label: 'Incluídos', value: 'included' },
  { label: 'Excluídos', value: 'excluded' },
  { label: 'Todos', value: 'all' },
];

const activeIndex = ref(0); // Para focar no artigo atual via teclado

const changeFilter = (value) => {
  router.get(route('projects.screening', props.project.id), { filter: value }, { preserveState: true });
};

const setStatus = (articleId, status) => {
  router.put(route('articles.status.update', articleId), { status }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      // Avança o foco para o próximo artigo
      if (activeIndex.value < props.articles.data.length - 1) {
        activeIndex.value++;
      } else if (props.articles.next_page_url) {
        // Se foi o último da página, vai para a próxima página automaticamente
        router.get(props.articles.next_page_url);
      }
    }
  });
};

// Escuta eventos de teclado para Triagem Ágil
const handleKeydown = (e) => {
  if (props.articles.data.length === 0) return;
  
  const currentArticle = props.articles.data[activeIndex.value];
  
  if (e.key === 'ArrowRight') {
    setStatus(currentArticle.id, 'included');
  } else if (e.key === 'ArrowLeft') {
    setStatus(currentArticle.id, 'excluded');
  } else if (e.key === 'ArrowDown' && activeIndex.value < props.articles.data.length - 1) {
    activeIndex.value++;
  } else if (e.key === 'ArrowUp' && activeIndex.value > 0) {
    activeIndex.value--;
  }
};

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));
</script>