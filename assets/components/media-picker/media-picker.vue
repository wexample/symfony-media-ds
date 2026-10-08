<script>
import FormField from '@wexample/symfony-design-system/components/_abstract/form-field/form-field.vue';
import MediaLibrary from '../media-library/media-library.vue';
import { showInTarget, TARGET_MODAL } from '@wexample/symfony-design-system/js/Helper/TargetHelper';

const T = 'WexampleSymfonyMediaDsBundle.components.media-picker.media-picker::';

// A field whose value is one media or several, picked in the media library.
//
// What the form submits is their ids, comma separated, in a hidden input like
// any other field; what the person sees is the media themselves, a thumbnail
// and a name each, and the way to the library, which opens in a modal as a
// picker. In the manner of symfony-search's entity-search-input.
export default {
  extends: FormField,

  template: '#vue-template-wexample-symfony-media-ds-bundle-components-media-picker-media-picker',

  emits: ['update:modelValue'],

  props: {
    modelValue: { type: String, default: '' },
    multiple: { type: Boolean, default: false },
    // The media the field opens with, described as the library lists them.
    selected: { type: Array, default: () => [] },
    // The options of the library opened to pick (media_library_options()).
    library: { type: Object, required: true },
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

    // The library in a modal, as a picker: what is chosen there comes back
    // here, added to what the field holds, or in its place for a single one.
    async openLibrary() {
      const body = document.createElement('div');
      body.className = 'media-picker--library';

      let modal = null;
      // An app of its own, in the modal, given the props the loader gives
      // the root of every vue it mounts: the render node it translates
      // through — this field's —, its catalogue, and its view.
      const vueService = this.app.getServiceOrFail('vue');
      const vueApp = vueService.createApp(
        {
          ...MediaLibrary,
          props: {
            ...MediaLibrary.props,
            rootComponent: { type: Object, default: null },
            translations: { type: Object, default: null },
            viewPath: { type: String, default: '@WexampleSymfonyMediaDsBundle/components/media-library' }
          }
        },
        {
          rootComponent: this.$root.rootComponent,
          translations: this.$root.translations,
          app: this.app,
          ...this.library,
          multiple: this.multiple,
          picker: true,
          onChoose: (chosen) => {
            this.take(chosen);
            vueApp.unmount();
            modal?.close();
          }
        }
      );

      // Its components by name, as the loader registers those of the vues it
      // mounts: the explorer's tree draws its nodes by name, recursively.
      vueService.registerComponentsRecursively(vueApp, { MediaLibrary });
      vueApp.mount(body);
      modal = await showInTarget(this.app, TARGET_MODAL, { title: this.t('library'), body });
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
