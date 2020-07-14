<template>
  <select
    id="{{id}}"
    name="$field->getName()"
    class="{{class}}"
    title="$field->getRenderable(static::LABEL, '')"
    {{required}}
    {{multiple}}
    disabled="disabled"
    autocomplete="off"
  >
    <option v-for="o in options" v-bind:key="o.id" :value="o.id">
      {{ o.name }}
    </option>
  </select>
</template>
<script>
import axios from "axios";

export default {
  data() {
    return {
      options: [],
    };
  },
  methods: {
    async fetch() {
      const data = axios.post("/graphql", {
        query: "{  }",
      });
      // this.options = res.data.data.xxx;
    },
  },
};
</script>
