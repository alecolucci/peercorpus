<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js';
import { Bar, Doughnut } from 'vue-chartjs';
import html2pdf from 'html2pdf.js';

// Regista os componentes do Chart.js
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const props = defineProps({
    project: Object,
    articles: Array,
    tags: Array,
    pivotData: Array,
});

const activeTab = ref('matrix'); // 'matrix' ou 'stats'

// Verifica se um artigo possui uma determinada tag
const hasTag = (articleId, tagId) => {
    return props.pivotData.some(item => item.article_id === articleId && item.tag_id === tagId);
};

// --- DADOS PARA OS GRÁFICOS ---

// 1. Frequência de cada Código (Quantos artigos usam cada tag)
const tagFrequencyData = computed(() => {
    const counts = props.tags.map(tag => {
        return props.pivotData.filter(item => item.tag_id === tag.id).length;
    });

    return {
        labels: props.tags.map(t => t.name),
        datasets: [{
            label: 'Artigos Codificados',
            data: counts,
            backgroundColor: props.tags.map(t => t.color || '#4F46E5'),
            borderWidth: 1
        }]
    };
});

// 2. Distribuição por Ano de Publicação
const publicationYearData = computed(() => {
    const yearCounts = {};
    props.articles.forEach(article => {
        const year = article.publication_year || 'Desconhecido';
        yearCounts[year] = (yearCounts[year] || 0) + 1;
    });

    // Ordena por ano
    const sortedYears = Object.keys(yearCounts).sort();
    const counts = sortedYears.map(year => yearCounts[year]);

    return {
        labels: sortedYears,
        datasets: [{
            label: 'Artigos por Ano',
            data: counts,
            backgroundColor: '#6366F1',
            borderRadius: 4,
        }]
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false }
    }
};

// Texto descritivo gerado dinamicamente para o relatório
const reportSummary = computed(() => {
    const totalArticles = props.articles.length;
    const totalTags = props.tags.length;
    return `Este relatório apresenta a síntese de ${totalArticles} artigos incluídos na revisão sistemática do projeto "${props.project.title}". 
    O codebook é composto por ${totalTags} categorias analíticas. A matriz abaixo cruza a ocorrência destes temas na literatura selecionada.`;
});

const downloadPDF = () => {
    // 1. Busca o elemento diretamente pelo ID (ignorando o sistema do Vue)
    const element = document.getElementById('report-container');
    
    if (!element) {
        alert("Erro: Container do relatório não encontrado.");
        return;
    }
    
    const opt = {
        margin:       10,
        filename:     `Relatorio_Sintese_${props.project.title}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }
    };

    html2pdf().set(opt).from(element).save();
};

// Função para exportar CSV (mantida da etapa anterior)
const exportToCsv = () => {
    let csvContent = "data:text/csv;charset=utf-8,";
    let headers = ["ID", "Título", "Autores", "Ano", ...props.tags.map(t => `"${t.name}"`)];
    csvContent += headers.join(",") + "\r\n";

    props.articles.forEach(article => {
        let row = [
            article.id,
            `"${article.title.replace(/"/g, '""')}"`,
            `"${(article.authors || '').replace(/"/g, '""')}"`,
            article.publication_year || '',
            ...props.tags.map(tag => hasTag(article.id, tag.id) ? 1 : 0)
        ];
        csvContent += row.join(",") + "\r\n";
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `matriz_sintese_${props.project.id}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <Head title="Matriz de Síntese e Estatísticas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Síntese e Análise: {{ project.title }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">Cruzamento qualitativo e estatísticas descritivas dos dados.</p>
                </div>
                <div class="flex gap-3 items-center">
                    <button @click="downloadPDF" class="bg-red-600 hover:bg-red-700 text-white font-bold text-sm py-2 px-4 rounded shadow transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Relatório PDF
                    </button>
                    <button @click="exportToCsv" class="bg-green-600 hover:bg-green-700 text-white font-bold text-sm py-2 px-4 rounded shadow transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Exportar CSV
                    </button>
                    <Link :href="route('coding.index', project.id)" class="text-sm text-indigo-600 hover:underline">
                        &larr; Voltar ao Workspace
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div id="report-container" class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6 bg-white p-4 rounded">
                
                <!-- Abas de Navegação (Matriz vs Estatísticas) -->
                <div class="flex border-b border-gray-200">
                    <button @click="activeTab = 'matrix'" 
                        :class="['py-3 px-6 font-semibold text-sm border-b-2 transition', activeTab === 'matrix' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                        Matriz de Cruzamento
                    </button>
                    <button @click="activeTab = 'stats'" 
                        :class="['py-3 px-6 font-semibold text-sm border-b-2 transition', activeTab === 'stats' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                        Estatísticas & Gráficos
                    </button>
                </div>

                <!-- CONTEÚDO DA ABA: MATRIZ -->
                <div v-show="activeTab === 'matrix'" class="bg-white shadow sm:rounded-lg overflow-hidden border border-gray-200">
                    <div class="overflow-x-auto max-h-[70vh]">
                        <table class="w-full border-collapse text-left text-sm text-gray-600">
                            <thead class="bg-gray-100 text-gray-700 uppercase text-xs sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th class="p-4 border-b border-r bg-gray-100 min-w-[300px]">Estudo / Artigo</th>
                                    <th class="p-4 border-b border-r bg-gray-100 text-center w-24">Ano</th>
                                    <th v-for="tag in tags" :key="tag.id" class="p-4 border-b border-r text-center min-w-[140px] max-w-[200px]">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: tag.color }"></span>
                                            <span class="truncate" :title="tag.name">{{ tag.name }}</span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="article in articles" :key="article.id" class="hover:bg-gray-50 transition">
                                    <td class="p-4 border-r bg-white font-medium text-gray-900">
                                        <div class="line-clamp-2" :title="article.title">{{ article.title }}</div>
                                        <div class="text-xs text-gray-400 font-normal mt-1 truncate">{{ article.authors || 'Autores não informados' }}</div>
                                    </td>
                                    <td class="p-4 border-r text-center bg-white whitespace-nowrap">
                                        {{ article.publication_year || 'N/A' }}
                                    </td>
                                    <td v-for="tag in tags" :key="tag.id" class="p-4 border-r text-center">
                                        <span v-if="hasTag(article.id, tag.id)" 
                                              class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 font-bold text-xs mx-auto shadow-sm">
                                            &check;
                                        </span>
                                        <span v-else class="text-gray-300">-</span>
                                    </td>
                                </tr>
                                <tr v-if="articles.length === 0">
                                    <td :colspan="tags.length + 2" class="p-8 text-center text-gray-500 italic">
                                        Nenhum artigo incluído encontrado para gerar a matriz.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- CONTEÚDO DA ABA: ESTATÍSTICAS E GRÁFICOS -->
                <div v-show="activeTab === 'stats'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Gráfico 1: Frequência de Códigos -->
                    <div class="bg-white p-6 shadow sm:rounded-lg border border-gray-200">
                        <h3 class="font-bold text-gray-800 text-base mb-4">Frequência dos Códigos (Temas mais recorrentes)</h3>
                        <div class="h-72 relative">
                            <Bar :data="tagFrequencyData" :options="chartOptions" />
                        </div>
                    </div>

                    <!-- Gráfico 2: Artigos por Ano de Publicação -->
                    <div class="bg-white p-6 shadow sm:rounded-lg border border-gray-200">
                        <h3 class="font-bold text-gray-800 text-base mb-4">Distribuição Cronológica (Artigos por Ano)</h3>
                        <div class="h-72 relative">
                            <Bar :data="publicationYearData" :options="chartOptions" />
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>