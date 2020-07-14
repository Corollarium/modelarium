<template> </template>
<script>
import FormulariumBase from './FormulariumBase';

export default {
    extends: FormulariumBase,

    data() {
        return {
            type: '{{modelName}}',
            fields: {{jsonData}}
        };
    }
};
</script>
<style></style>
