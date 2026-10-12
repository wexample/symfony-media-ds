<script>
import FileExplorer from '@wexample/symfony-design-system/components/file-explorer/file-explorer.vue';
import { unwrapApiEnvelope } from '@wexample/js-api-entity/Common/ApiEnvelope';

const T = 'WexampleSymfonyMediaDsBundle.components.media-library.media-library::';
// What a level of the library is asked for at a time.
const PAGE_LENGTH = 60;

// The media library: symfony-design-system's file explorer, fed by
// symfony-media's API, with what arranging it takes — an upload into the
// folder shown, a new folder, a rename, a move by dragging onto a folder, a
// deletion — for whoever may manage it. As a picker (`picker`) — the page opened by a media field in a modal
// or a panel —, it adds the way to choose what is selected, tells the field
// (`media-library:choose`, with its id and the media items), and closes the
// page as a form does once sent.
export default {
  template: '#vue-template-wexample-symfony-media-ds-bundle-components-media-library-media-library',

  components: {
    FileExplorer
  },

  props: {
    listUrl: { type: String, required: true },
    canManage: { type: Boolean, default: false },
    // { folder, rename, move, delete }, given to whoever may manage.
    manageUrls: { type: Object, default: null },
    uploadUrl: { type: String, default: null },
    chunkSize: { type: Number, default: null },
    multiple: { type: Boolean, default: true },
    picker: { type: Boolean, default: false },
    // The id of the field a picker answers.
    field: { type: String, default: '' },
    stateId: { type: String, default: 'media-library' },
    // The media listed, and the only files an upload takes: `image/*`.
    accept: { type: String, default: '' }
  },

  emits: ['choose'],

  data() {
    return {
      folder: null,
      selected: [],
      // A folder being named, or an item being renamed: { mode, item, name }.
      naming: null,
      uploading: 0
    };
  },

  computed: {
    chosenFiles() {
      return this.selected.filter((item) => item.type === 'file');
    }
  },

  mounted() {
    this.app.services.events.listen('upload:queue-empty', this.onUploadsDone);
  },

  beforeUnmount() {
    this.app.services.events.forget('upload:queue-empty', this.onUploadsDone);
  },

  methods: {
    t(key, parameters = {}) {
      return this.trans(`${T}${key}`, parameters);
    },

    renderIcon(name) {
      return this.app.getServiceOrFail('icon').icon(name);
    },

    // A folder's path is the ids leading to it: its own id is the last one.
    folderId(folder) {
      return folder?.path ? folder.path.split('/').pop() : '';
    },

    async loadChildren(folder, page) {
      const query = new URLSearchParams({ folder: this.folderId(folder), page: String(page), length: String(PAGE_LENGTH), accept: this.accept });
      const response = await fetch(`${this.listUrl}?${query}`, { headers: { Accept: 'application/json' } });
      const data = unwrapApiEnvelope(await response.json());

      return { items: data.items, total: data.pagination?.total ?? data.items.length };
    },

    onNavigate() {
      this.selected = [];
      this.naming = null;
    },

    onSelect(items) {
      this.selected = items;
    },

    // A media opened is shown in a new tab; in a picker, it is chosen.
    onOpen(item) {
      if (this.picker) {
        this.answer([item]);
      } else {
        window.open(item.url, '_blank', 'noopener');
      }
    },

    choose() {
      if (this.chosenFiles.length) {
        this.answer(this.chosenFiles);
      }
    },

    answer(items) {
      this.$emit('choose', items);
      this.app.services.events.trigger('media-library:choose', { field: this.field, items });
      // Closed as a form closes the modal or the panel it was sent from.
      this.$el.dispatchEvent(new CustomEvent('embed:close', {
        bubbles: true,
        detail: { source: { el: this.$el }, instant: false }
      }));
    },

    refresh() {
      this.$refs.explorer?.refresh();
    },

    pickFiles() {
      this.$refs.files?.click();
    },

    // Sent to the folder shown, in pieces, by the loader's upload queue; the
    // listing comes back once the queue is empty.
    onFilesPicked(event) {
      const files = Array.from(event.target.files ?? []);
      event.target.value = '';

      if (!files.length) {
        return;
      }

      const separator = this.uploadUrl.includes('?') ? '&' : '?';
      this.uploading += files.length;
      this.app.getServiceOrFail('uploads').enqueueFiles(
        files,
        { path: `${this.uploadUrl}${separator}folder=${encodeURIComponent(this.folderId(this.folder))}`, chunkSize: this.chunkSize ?? undefined },
        { source: this.$el, name: 'media' }
      );
    },

    onUploadsDone() {
      if (this.uploading) {
        this.uploading = 0;
        this.refresh();
      }
    },

    startNaming(mode) {
      const item = mode === 'rename' ? this.selected[0] : null;
      this.naming = { mode, item, name: item?.name ?? '' };
      this.$nextTick(() => this.$refs.name?.focus());
    },

    async submitName() {
      const { mode, item, name } = this.naming;

      if (!name.trim()) {
        return;
      }

      const done = mode === 'rename'
        ? await this.call('rename', { type: this.kind(item), id: item.id, name })
        : await this.call('folder', { name, parent: this.folderId(this.folder) });

      if (done) {
        this.naming = null;
        this.refresh();
      }
    },

    async remove() {
      const answer = await this.app.getServiceOrFail('confirm').confirm({
        title: this.t('delete.title'),
        message: this.t('delete.message', { '%count%': this.selected.length }),
        preset: 'yes_no'
      });

      if (answer !== 'yes') {
        return;
      }

      for (const item of this.selected) {
        if (!(await this.call('delete', { type: this.kind(item), id: item.id }))) {
          break;
        }
      }

      this.refresh();
    },

    // Dropped on a folder of the listing or of the path: each item moved in
    // turn, the first refusal told and the rest left where they are.
    async onMove({ items, target }) {
      for (const item of items) {
        if (!(await this.call('move', { type: this.kind(item), id: item.id, target: this.folderId(target) }))) {
          break;
        }
      }

      this.refresh();
    },

    kind(item) {
      return item.type === 'directory' ? 'folder' : 'media';
    },

    // What the library refuses — a folder still full, a name left empty —
    // is told in a toast, and the screen stays as it was.
    async call(action, body) {
      const response = await fetch(this.manageUrls[action], {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(body)
      });

      try {
        unwrapApiEnvelope(await response.json());

        return response.ok;
      } catch (error) {
        this.app.getServiceOrFail('toast').show({ type: 'error', message: error.message });

        return false;
      }
    }
  }
};
</script>
