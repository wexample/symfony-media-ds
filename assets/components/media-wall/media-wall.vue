<script>
import AbstractEntityWall from '@wexample/symfony-design-system/components/collection/wall/abstract-entity-wall/abstract-entity-wall.vue';
import Media from '@wexample/symfony-media/Entity/Media';

// What the browser is told of the width a tile takes, column by column as
// the wall counts them (css/shapes/_wall.scss): the thumbnail it picks in
// the srcset is the narrowest one wider than that.
const TILE_SIZES = '(min-width: 62rem) 25vw, (min-width: 46rem) 33vw, (min-width: 30rem) 50vw, 100vw';

// The media of the library hung on a wall, read from its api a page at a
// time as the wall is scrolled: the whole library or one folder, images
// unless `accept` says otherwise. Each tile shows the thumbnails the server
// draws and leads to the file. The twin of media_wall(), which draws a list
// of media the page already holds.
export default {
  extends: AbstractEntityWall,

  template: '#vue-template-wexample-symfony-media-ds-bundle-components-media-wall-media-wall',

  props: {
    // A folder's id, or the whole library when empty.
    folder: {
      type: String,
      default: ''
    },
    // What is listed, as an `accept` attribute says it.
    accept: {
      type: String,
      default: 'image/*'
    },
    // The name of each media over its foot.
    captions: {
      type: Boolean,
      default: true
    }
  },

  methods: {
    getEntityClass() {
      return Media;
    },

    getEntitiesFetchParams() {
      return { query: { folder: this.folder, accept: this.accept } };
    },

    getTileConfiguration(media) {
      const metadata = media.metadata ?? {};
      const thumbnails = Object.entries(metadata.thumbnails ?? {})
        .map(([width, url]) => ({ width: Number(width), url }))
        .sort((left, right) => left.width - right.width);
      const largest = thumbnails.at(-1);

      return {
        src: largest?.url ?? null,
        srcset: thumbnails.map(({ width, url }) => `${url} ${width}w`).join(', ') || null,
        sizes: thumbnails.length ? TILE_SIZES : null,
        alt: media.name,
        width: media.width,
        height: media.height,
        href: metadata.url ?? null,
        caption: this.captions ? media.name : null,
        class: null
      };
    }
  }
};
</script>
