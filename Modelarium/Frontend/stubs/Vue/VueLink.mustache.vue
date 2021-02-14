<template>
  <router-link class="modelarium-link {|lowerName|}-link" :to="link">
    {{ {| titleField |} }}
  </router-link>
</template>
<script>
{|{imports}|}

export default {
    {|{otherData}|}
    data() {
        return {|{jsonData}|};
    },
    computed: { {|{computedCode}|} },
    props: {|{propsCode}|},
    methods: {
      escapeIdentifier(identifier) { {|{options.escapeIdentifierBody}|} },
      {|{methodsCode}|}
    }
};
</script>
<style></style>
