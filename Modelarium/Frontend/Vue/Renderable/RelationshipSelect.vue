<template>
  <select
    :name="name"
    class="modelarium-relationshipselect__select"
    :required="required"
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
  props: {
    name: {
      type: String,
    },
    required: {
      type: Boolean,
      default: false,
    },
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
