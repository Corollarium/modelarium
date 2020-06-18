<template>
  <div>
    {{ form }}
  </div>
</template>

<script>
import {{modelName}}Base from './{{modelName}}Base';
import crudShow from './formularium/crudShow.js';

export default {
    extends: {{modelName}}Base,
    mixins: [crudShow],

    data() {
        return {
        }
    },

    components: {
    },

    methods: {
    }
};
</script>
<style></style>
