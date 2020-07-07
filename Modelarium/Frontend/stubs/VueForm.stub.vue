<template>
<{{containerTag}}>
    <form method="POST" action="/api/{{modelNameLower}}">
        {{form}}

        <button type="submit">
            Save
        </button>
    </form>
</{{containerTag}}>
</template>

<script>
import {{modelName}}Base from './{{modelName}}Base';
import crudEditMixin from './formularium/crudEdit.js';

export default {
    extends: {{modelName}}Base,
    mixins: [crudEditMixin],

    data() {
        return {{jsonData}};
    },

    methods: {
        changedFile(name, event) {
            // this[name]
        }
    }
}
</script>
<style>
</style>

