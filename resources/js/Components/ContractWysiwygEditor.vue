<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import TextAlign from '@tiptap/extension-text-align';
import { Table, TableRow, TableCell, TableHeader } from '@tiptap/extension-table';
import { watch, ref, onBeforeUnmount } from 'vue';
import { 
    TagIcon, ChevronDownIcon, ArrowUturnLeftIcon, ArrowUturnRightIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Ketik isi klausul atau draf perjanjian kerja...',
    },
    availableTags: {
        type: Array,
        default: () => [
            { tag: '{COMPANY_NAME}', label: 'Nama Perusahaan' },
            { tag: '{COMPANY_ADDRESS}', label: 'Alamat Perusahaan' },
            { tag: '{NOMOR_KONTRAK}', label: 'Nomor Kontrak' },
            { tag: '{NAMA_KARYAWAN}', label: 'Nama Karyawan' },
            { tag: '{NIK_KARYAWAN}', label: 'NIK Karyawan' },
            { tag: '{TEMPAT_TGL_LAHIR}', label: 'Tempat, Tgl Lahir' },
            { tag: '{ALAMAT_KARYAWAN}', label: 'Alamat Karyawan' },
            { tag: '{JABATAN}', label: 'Jabatan' },
            { tag: '{DEPARTEMEN}', label: 'Departemen' },
            { tag: '{LOKASI_KANTOR}', label: 'Lokasi Kantor' },
            { tag: '{TANGGAL_BERGABUNG}', label: 'Tanggal Bergabung' },
            { tag: '{TANGGAL_BERAKHIR}', label: 'Tanggal Berakhir' },
            { tag: '{DURASI_KONTRAK}', label: 'Durasi Kontrak' },
            { tag: '{DURASI_PROBATION}', label: 'Durasi Probation' },
            { tag: '{TANGGAL_SELESAI_PROBATION}', label: 'Tgl Selesai Probation' },
            { tag: '{GAJI_POKOK}', label: 'Gaji Pokok' },
            { tag: '{TUNJANGAN_RUTIN}', label: 'Tunjangan Rutin' },
            { tag: '{REKENING_BANK}', label: 'Rekening Bank' },
            { tag: '{JAM_KERJA_MINGGUAN}', label: 'Jam Kerja Mingguan' },
            { tag: '{NOTICE_PERIOD}', label: 'Notice Period (Hari)' },
            { tag: '{WORKING_SCOPE}', label: 'Daftar Tupoksi Kerja' },
        ],
    },
    editable: {
        type: Boolean,
        default: true,
    },
    minHeight: {
        type: String,
        default: '380px',
    },
});

const emit = defineEmits(['update:modelValue']);
const showTagDropdown = ref(false);

const editor = useEditor({
    content: props.modelValue,
    editable: props.editable,
    extensions: [
        StarterKit.configure({
            heading: {
                levels: [1, 2, 3, 4],
            },
        }),
        Underline,
        TextAlign.configure({
            types: ['heading', 'paragraph'],
        }),
        Table.configure({
            resizable: true,
        }),
        TableRow,
        TableHeader,
        TableCell,
    ],
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

watch(() => props.modelValue, (newVal) => {
    if (editor.value && editor.value.getHTML() !== newVal) {
        editor.value.commands.setContent(newVal || '', false);
    }
});

watch(() => props.editable, (newVal) => {
    if (editor.value) {
        editor.value.setEditable(newVal);
    }
});

onBeforeUnmount(() => {
    if (editor.value) {
        editor.value.destroy();
    }
});

const insertTag = (tag) => {
    if (editor.value) {
        editor.value.chain().focus().insertContent(` <strong>${tag}</strong> `).run();
    }
    showTagDropdown.value = false;
};
</script>

<template>
    <div class="border border-gray-300 dark:border-gray-700 rounded-xl overflow-hidden bg-white dark:bg-gray-900 shadow-2xs transition-all">
        <!-- WYSIWYG Toolbar -->
        <div v-if="editor && editable" class="flex flex-wrap items-center gap-1 p-2 bg-gray-50/90 dark:bg-gray-800/90 border-b border-gray-200 dark:border-gray-700 text-xs">
            <!-- Text Styles Dropdown/Buttons -->
            <button
                type="button"
                @click="editor.chain().focus().setParagraph().run()"
                :class="[editor.isActive('paragraph') ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 font-bold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700']"
                class="px-2 py-1 rounded transition-colors"
                title="Paragraf Normal"
            >
                P
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                :class="[editor.isActive('heading', { level: 2 }) ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 font-bold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700']"
                class="px-2 py-1 rounded transition-colors font-bold"
                title="Judul Pasal (Heading 2)"
            >
                H2
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                :class="[editor.isActive('heading', { level: 3 }) ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 font-bold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700']"
                class="px-2 py-1 rounded transition-colors font-bold"
                title="Sub-Judul (Heading 3)"
            >
                H3
            </button>

            <span class="w-px h-4 bg-gray-300 dark:bg-gray-600 mx-1"></span>

            <!-- Formatting -->
            <button
                type="button"
                @click="editor.chain().focus().toggleBold().run()"
                :class="[editor.isActive('bold') ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700']"
                class="px-2 py-1 rounded font-bold transition-colors"
                title="Tebal (Ctrl+B)"
            >
                B
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleItalic().run()"
                :class="[editor.isActive('italic') ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700']"
                class="px-2 py-1 rounded italic transition-colors"
                title="Miring (Ctrl+I)"
            >
                I
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleUnderline().run()"
                :class="[editor.isActive('underline') ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700']"
                class="px-2 py-1 rounded underline transition-colors"
                title="Garis Bawah (Ctrl+U)"
            >
                U
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleStrike().run()"
                :class="[editor.isActive('strike') ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700']"
                class="px-2 py-1 rounded line-through transition-colors"
                title="Coret"
            >
                S
            </button>

            <span class="w-px h-4 bg-gray-300 dark:bg-gray-600 mx-1"></span>

            <!-- Alignment -->
            <button
                type="button"
                @click="editor.chain().focus().setTextAlign('left').run()"
                :class="[editor.isActive({ textAlign: 'left' }) ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 font-bold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700']"
                class="px-2 py-1 rounded transition-colors"
                title="Rata Kiri"
            >
                ⇤
            </button>
            <button
                type="button"
                @click="editor.chain().focus().setTextAlign('center').run()"
                :class="[editor.isActive({ textAlign: 'center' }) ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 font-bold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700']"
                class="px-2 py-1 rounded transition-colors"
                title="Rata Tengah"
            >
                ↔
            </button>
            <button
                type="button"
                @click="editor.chain().focus().setTextAlign('right').run()"
                :class="[editor.isActive({ textAlign: 'right' }) ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 font-bold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700']"
                class="px-2 py-1 rounded transition-colors"
                title="Rata Kanan"
            >
                ⇥
            </button>
            <button
                type="button"
                @click="editor.chain().focus().setTextAlign('justify').run()"
                :class="[editor.isActive({ textAlign: 'justify' }) ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 font-bold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700']"
                class="px-2 py-1 rounded transition-colors"
                title="Rata Kiri-Kanan (Justify)"
            >
                ≡
            </button>

            <span class="w-px h-4 bg-gray-300 dark:bg-gray-600 mx-1"></span>

            <!-- Lists -->
            <button
                type="button"
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="[editor.isActive('bulletList') ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700']"
                class="px-2 py-1 rounded transition-colors"
                title="Bullet List"
            >
                • List
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleOrderedList().run()"
                :class="[editor.isActive('orderedList') ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700']"
                class="px-2 py-1 rounded transition-colors"
                title="Numbered List"
            >
                1. List
            </button>
            <button
                type="button"
                @click="editor.chain().focus().setHorizontalRule().run()"
                class="px-2 py-1 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                title="Garis Pemisah"
            >
                ―
            </button>

            <span class="w-px h-4 bg-gray-300 dark:bg-gray-600 mx-1"></span>

            <!-- History -->
            <button
                type="button"
                @click="editor.chain().focus().undo().run()"
                :disabled="!editor.can().undo()"
                class="p-1 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30 transition-colors"
                title="Undo (Ctrl+Z)"
            >
                <ArrowUturnLeftIcon class="w-3.5 h-3.5" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().redo().run()"
                :disabled="!editor.can().redo()"
                class="p-1 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30 transition-colors"
                title="Redo (Ctrl+Y)"
            >
                <ArrowUturnRightIcon class="w-3.5 h-3.5" />
            </button>

            <div class="ml-auto relative">
                <button
                    type="button"
                    @click="showTagDropdown = !showTagDropdown"
                    class="inline-flex items-center px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-medium shadow-2xs transition-colors"
                >
                    <TagIcon class="w-3.5 h-3.5 mr-1" />
                    Sisipkan Tag Dinamis
                    <ChevronDownIcon class="w-3 h-3 ml-1" />
                </button>

                <!-- Tag Dropdown Menu -->
                <div 
                    v-if="showTagDropdown"
                    class="absolute right-0 mt-1 w-64 max-h-60 overflow-y-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50 p-1 divide-y divide-gray-100 dark:divide-gray-700"
                >
                    <div class="p-1.5 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                        Pilih Tag Variabel:
                    </div>
                    <div class="py-1">
                        <button
                            v-for="item in availableTags"
                            :key="typeof item === 'string' ? item : item.tag"
                            type="button"
                            @click="insertTag(typeof item === 'string' ? item : item.tag)"
                            class="w-full text-left px-2.5 py-1.5 text-xs hover:bg-indigo-50 dark:hover:bg-indigo-950/50 rounded flex items-center justify-between text-gray-800 dark:text-gray-200 group"
                        >
                            <span class="font-mono text-indigo-600 dark:text-indigo-400 font-semibold group-hover:underline">
                                {{ typeof item === 'string' ? item : item.tag }}
                            </span>
                            <span v-if="item.label" class="text-[10px] text-gray-400 ml-1">
                                {{ item.label }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Editor Content Area -->
        <div 
            class="p-4 prose prose-sm dark:prose-invert max-w-none focus:outline-hidden"
            :style="{ minHeight: minHeight }"
            @click="editor?.commands.focus()"
        >
            <EditorContent :editor="editor" class="wysiwyg-contract-body" />
        </div>
    </div>
</template>

<style>
/* TipTap Prose Styling for Contract Documents */
.wysiwyg-contract-body .tiptap {
    min-height: 250px;
    outline: none;
    font-size: 0.875rem;
    line-height: 1.6;
}

.wysiwyg-contract-body .tiptap h1 {
    font-size: 1.25rem;
    font-weight: 800;
    margin-top: 1rem;
    margin-bottom: 0.5rem;
    text-align: center;
    text-transform: uppercase;
}

.wysiwyg-contract-body .tiptap h2 {
    font-size: 1rem;
    font-weight: 700;
    margin-top: 1rem;
    margin-bottom: 0.35rem;
    padding: 0.25rem 0.5rem;
    background-color: rgba(243, 244, 246, 0.7);
    border-top: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
    color: #111827;
}

.dark .wysiwyg-contract-body .tiptap h2 {
    background-color: rgba(31, 41, 55, 0.7);
    border-color: #374151;
    color: #f3f4f6;
}

.wysiwyg-contract-body .tiptap h3 {
    font-size: 0.925rem;
    font-weight: 600;
    margin-top: 0.75rem;
    margin-bottom: 0.25rem;
}

.wysiwyg-contract-body .tiptap p {
    margin-top: 0.35rem;
    margin-bottom: 0.35rem;
    text-align: justify;
}

.wysiwyg-contract-body .tiptap ol, 
.wysiwyg-contract-body .tiptap ul {
    padding-left: 1.25rem;
    margin-top: 0.35rem;
    margin-bottom: 0.35rem;
}

.wysiwyg-contract-body .tiptap ol {
    list-style-type: decimal;
}

.wysiwyg-contract-body .tiptap ul {
    list-style-type: disc;
}

.wysiwyg-contract-body .tiptap li {
    margin-top: 0.15rem;
    margin-bottom: 0.15rem;
    text-align: justify;
}

.wysiwyg-contract-body .tiptap hr {
    border-color: #e5e7eb;
    margin: 1rem 0;
}

.dark .wysiwyg-contract-body .tiptap hr {
    border-color: #374151;
}

.wysiwyg-contract-body .tiptap blockquote {
    border-left: 3px solid #6366f1;
    padding-left: 0.75rem;
    color: #6b7280;
    font-style: italic;
    margin: 0.5rem 0;
}
</style>
