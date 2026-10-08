<script>
import FormField from '@wexample/symfony-design-system/components/_abstract/form-field/form-field.vue';
import { loadIntoTarget, TARGET_MODAL } from '@wexample/symfony-design-system/js/Helper/TargetHelper';

const T = 'WexampleSymfonyMediaDsBundle.components.media-picker.media-picker::';

// A field whose value is one media or several, picked in the media library.
//
// What the form submits is their ids, comma separated, in a hidden input like
// any other field; what the person sees is the media themselves, a thumbnail
// and a name each, and the way to the library page, opened as a picker in a
// modal or a panel. In the manner of symfony-search's entity-search-input.
export default {
  extends: FormField,

  template: '#vue-template-wexample-symfony-media-ds-bundle-components-media-picker-media-picker',

  emits: ['update:modelValue'],

  props: {
    modelValue: { type: String, default: '' },
    multiple: { type: Boolean, default: false },
    // The media the field opens with, described as the library lists them.
    selected: { type: Array, default: () => [] },
    // The library page (media_library_url()), opened as a picker.
    libraryUrl: { type: String, required: true },
    // Where it opens: `modal` or `panel`.
    target: { type: String, default: TARGET_MODAL },
    // The wording, resolved by the server (media_picker_labels()).
    labels: { type: Object, default: () => ({}) }
  },

  data() {
    return {
      // Held here and not only read from the prop: drawn from Twig the field
      // has no parent binding it, and the hidden input posts what is here.
      items: [...this.selected]
    };
  },

  mounted() {
    this.app.services.events.listen('media-library:choose', this.onChoose);
  },

  beforeUnmount() {
    this.app.services.events.forget('media-library:choose', this.onChoose);
  },

  computed: {
    value() {
      return this.items.map((item) => item.id).join(',');
    }
  },

  methods: {
    t(key, parameters = {}) {
      return this.labels[key] ?? this.trans(`${T}${key}`, parameters);
    },

    renderIcon(name) {
      return this.app.getServiceOrFail('icon').icon(name);
    },

    // The library page, as a picker, in a modal or a panel — loaded the way
    // any page is: what is chosen there comes back by an event naming this
    // field, added to what it holds, or in its place for a single one.
    openLibrary() {
      const query = new URLSearchParams({ picker: '1', multiple: this.multiple ? '1' : '0', field: this.resolvedId });
      const separator = this.libraryUrl.includes('?') ? '&' : '?';

      loadIntoTarget(this.app, this.target, `${this.libraryUrl}${separator}${query}`, {
        closeOnEscape: true,
        closeOnOverlayClick: true,
        // A library is browsed: the window it opens in is sized by its edges,
        // and keeps the size it was left at.
        resizable: true,
        resizeState: 'media-library'
      });
    },

    onChoose(event) {
      if (event.detail?.field === this.resolvedId) {
        this.take(event.detail.items);
      }
    },

    take(chosen) {
      const items = this.multiple
        ? [...this.items, ...chosen.filter((item) => !this.items.some((held) => held.id === item.id))]
        : chosen.slice(0, 1);

      this.setItems(items);
    },

    remove(item) {
      this.setItems(this.items.filter((held) => held.id !== item.id));
    },

    setItems(items) {
      this.items = items;
      this.$emit('update:modelValue', this.value);
    },

    // Picking is not writing: an assistant fills the ids in directly.
    async writeValueAssisted(value) {
      const ids = String(value ?? '').split(',').filter(Boolean);
      this.setItems(this.items.filter((item) => ids.includes(item.id)));
    }
  }
};
</script>
