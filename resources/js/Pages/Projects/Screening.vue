<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">Triagem (Screening): {{ project.title }}</h2>
          <p class="text-sm text-gray-500 mt-1">
            Utilize as <kbd class="px-2 py-1 bg-gray-100 border rounded-md text-xs font-mono">Setas</kbd> do teclado (Esquerda/Direita) para avaliar rapidamente.
          </p>
        </div>
        <Link :href="route('projects.show', project.id)" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
          &larr; Voltar ao Projeto
        </Link>
      </div>
    </template>

    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
      
      <!-- Abas (Filtros de Status) -->
      <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
        <Link :href="route('projects.screening', { project: project.id, filter: 'pending' })" 
              :class="currentFilter === 'pending' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-200'" 
              class="px-4 py-2 rounded-md text-sm font-medium border transition">
          Pendentes ({{ stats.pending }})
        </Link>
        
        <Link :href="route('projects.screening', { project: project.id, filter: 'conflict' })" 
              :class="currentFilter === 'conflict' ? 'bg-orange-500 text-white border-orange-600 shadow-sm' : 'bg-white text-orange-600 hover:bg-orange-50 border-orange-200'" 
              class="px-4 py-2 rounded-md text-sm font-medium border transition flex gap-2 items-center">
          Conflitos 
          <span v-if="stats.conflict > 0" class="bg-white text-orange-600 text-xs px-2 py-0.5 rounded-full font-bold">{{ stats.conflict }}</span>
        </Link>
        
        <Link :href="route('projects.screening', { project: project.id, filter: 'included' })" 
              :class="currentFilter === 'included' ? 'bg-green-600 text-white border-green-600 shadow-sm' : 'bg-white text-green-700 hover:bg-green-50 border-green-200'" 
              class="px-4 py-2 rounded-md text-sm font-medium border transition">
          Aceitos ({{ stats.included }})
        </Link>
        
        <Link :href="route('projects.screening', { project: project.id, filter: 'excluded' })" 
              :class="currentFilter === 'excluded' ? 'bg-red-600 text-white border-red-600 shadow-sm' : 'bg-white text-red-700 hover:bg-red-50 border-red-200'" 
              class="px-4 py-2 rounded-md text-sm font-medium border transition">
          Rejeitados ({{ stats.excluded }})
        </Link>
        
        <Link :href="route('projects.screening', { project: project.id, filter: 'all' })" 
              :class="currentFilter === 'all' ? 'bg-gray-800 text-white border-gray-800 shadow-sm' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-200'" 
              class="px-4 py-2 rounded-md text-sm font-medium border transition ml-auto">
          Todos ({{ stats.total }})
        </Link>
      </div>

      <!-- Lista de Artigos -->
      <div v-if="articles.data.length > 0" class="space-y-4">
        <div v-for="(article, index) in articles.data" :key="article.id"
             :class="[
               'bg-white rounded-lg p-6 transition-all duration-200',
               activeIndex === index ? 'ring-2 ring-indigo-500 shadow-md transform scale-[1.01]' : 'border border-gray-200 shadow-sm opacity-60 hover:opacity-100 cursor-pointer'
             ]"
             @click="activeIndex = index"
        >
          <!-- Metadados -->
          <div class="flex justify-between items-start mb-3">
            <h3 class="text-lg font-bold text-gray-900 leading-tight flex-1">{{ article.title }}</h3>
            <span v-if="article.doi" class="ml-4 text-xs font-mono text-gray-400 bg-gray-100 px-2 py-1 rounded">DOI: {{ article.doi }}</span>
          </div>
          
          <p class="text-sm text-indigo-600 font-medium mb-4">
            {{ article.authors }} <span v-if="article.publication_year">({{ article.publication_year }})</span>
          </p>
          
          <!-- Resumo -->
          <div class="text-gray-700 text-sm leading-relaxed mb-6">
            <h4 class="font-bold text-xs uppercase text-gray-500 mb-1 tracking-wider">Abstract</h4>
            <p v-if="article.abstract">{{ article.abstract }}</p>
            <p v-else class="italic text-gray-400">Nenhum resumo disponível.</p>
          </div>

          <!-- Histórico de Votos da Equipe -->
          <div v-if="article.votes && article.votes.length > 0" class="mt-6 mb-6 bg-gray-50 border border-gray-200 rounded-md p-4">
            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-3">Votos da Equipe</h4>
            <ul class="space-y-2">
              <li v-for="vote in article.votes" :key="vote.id" class="flex items-center gap-3 text-sm">
                <span class="font-medium text-gray-700 w-32 truncate" :title="vote.user.name">{{ vote.user.name }}</span>
                <span :class="vote.vote === 'included' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-0.5 rounded text-xs font-bold uppercase">
                  {{ vote.vote === 'included' ? 'Aceitou' : 'Rejeitou' }}
                </span>
                <span v-if="vote.is_resolution" class="bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded text-xs font-bold uppercase border border-yellow-300">
                  ★ Veredito Final
                </span>
              </li>
            </ul>

            <!-- PAINEL DE DESEMPATE (Apenas Admins veem isso em artigos conflitantes) -->
            <div v-if="article.status === 'conflict' && canManage" class="mt-4 pt-4 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <span class="text-sm font-bold text-orange-600 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                Aguardando Veredito Final
              </span>
              <div class="flex gap-2">
                <button @click.stop="setStatus(article.id, 'included', true)" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition shadow-sm">
                  Forçar Aceitação
                </button>
                <button @click.stop="setStatus(article.id, 'excluded', true)" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition shadow-sm">
                  Forçar Rejeição
                </button>
              </div>
            </div>
          </div>

          <!-- Ações Principais (Aparecem apenas no artigo que está com foco ativo) -->
          <div class="flex justify-between items-center border-t border-gray-100 pt-4" :class="{ 'opacity-0 h-0 overflow-hidden pt-0 border-none': activeIndex !== index }">
            <span class="text-xs text-gray-400">Pressione &larr; para Rejeitar ou &rarr; para Aceitar</span>
            <div class="flex gap-3">
              <button @click.stop="setStatus(article.id, 'excluded')" class="px-6 py-2 bg-red-100 hover:bg-red-200 text-red-700 font-medium rounded-md transition border border-red-200">
                &larr; Rejeitar
              </button>
              
              <button @click.stop="setStatus(article.id, 'pending')" v-if="currentFilter !== 'pending'" class="px-4 py-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 font-medium rounded-md transition text-sm">
                Desfazer
              </button>

              <button @click.stop="setStatus(article.id, 'included')" class="px-6 py-2 bg-green-100 hover:bg-green-200 text-green-800 font-medium rounded-md transition border border-green-200">
                Aceitar &rarr;
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Estado Vazio -->
      <div v-else class="text-center py-16 bg-white rounded-lg shadow-sm border border-gray-200 border-dashed">
        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum artigo encontrado aqui.</h3>
        <p class="mt-1 text-sm text-gray-500">Tudo limpo nesta categoria!</p>
      </div>

      <!-- Paginação do Laravel Corrigida -->
      <div class="mt-8 flex justify-center" v-if="articles.links && articles.links.length > 3">
        <div class="flex flex-wrap gap-1 justify-center">
          <template v-for="(link, i) in articles.links" :key="i">
            <!-- Renderiza um texto desabilitado se a URL for null -->
            <span v-if="link.url === null"
              v-html="link.label"
              class="px-4 py-2 text-sm border rounded-md bg-gray-50 text-gray-400 border-gray-200 cursor-not-allowed"
            ></span>
            
            <!-- Renderiza o Link clicável se a URL existir -->
            <Link v-else 
              :href="link.url"
              v-html="link.label"
              class="px-4 py-2 text-sm border rounded-md transition-colors"
              :class="link.active ? 'bg-indigo-600 text-white border-indigo-600 font-medium' : 'bg-white text-gray-600 hover:bg-gray-50 border-gray-300'"
            />
          </template>
        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
  project: Object,
  articles: Object,
  stats: Object,
  currentFilter: String,
  currentUserRole: String, // 'owner', 'admin' ou 'collaborator'
});

// Apenas Owners e Admins podem forçar resoluções de conflitos
const canManage = computed(() => ['owner', 'admin'].includes(props.currentUserRole));

// Índice do artigo que está com o "foco/cursor" na tela
const activeIndex = ref(0);

// Função que envia o voto (ou a resolução) para o backend
const setStatus = (articleId, status, isResolution = false) => {
  router.put(route('articles.status.update', articleId), { status, is_resolution: isResolution }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      // Navegação inteligente (Modo Esteira)
      if (props.currentFilter === 'all') {
        if (activeIndex.value < props.articles.data.length - 1) {
          activeIndex.value++;
        } else if (props.articles.next_page_url) {
          activeIndex.value = 0;
          router.get(props.articles.next_page_url);
        }
      } else {
        // Se a página ficar vazia, tenta puxar a próxima automaticamente
        if (props.articles.data.length === 0 && props.articles.next_page_url) {
          activeIndex.value = 0;
          router.get(props.articles.next_page_url);
        } 
        // Trava o foco para não sumir se ele votar no último item
        else if (activeIndex.value >= props.articles.data.length && props.articles.data.length > 0) {
          activeIndex.value = props.articles.data.length - 1;
        }
      }
    }
  });
};

// Navegação por Teclado
const handleKeydown = (e) => {
  if (props.articles.data.length === 0) return;
  const currentArticle = props.articles.data[activeIndex.value];
  if (!currentArticle) return;

  if (e.key === 'ArrowRight') {
    e.preventDefault();
    setStatus(currentArticle.id, 'included');
  } else if (e.key === 'ArrowLeft') {
    e.preventDefault();
    setStatus(currentArticle.id, 'excluded');
  } else if (e.key === 'ArrowDown') {
    e.preventDefault();
    if (activeIndex.value < props.articles.data.length - 1) activeIndex.value++;
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    if (activeIndex.value > 0) activeIndex.value--;
  }
};

onMounted(() => {
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
});
</script>
