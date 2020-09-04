<template>
  <select
    :name="name"
    :class="'modelarium-relationshipselect__select ' + htmlClass"
    :required="required"
    autocomplete="off"
    :disabled="isLoading"
  >
    <option v-if="isLoading" value="" selected data-default>loading</option>
    <option v-else v-for="o in options" v-bind:key="o.id" :value="o.id">
      {{ o[nameField] }}
    </option>
  </select>
</template>
<script>
import axios from "axios";

export default {
  data() {
    return {
      options: [],
      isLoading: true,
    };
  },
  props: {
    name: {
      type: String,
    },
    htmlClass: {
      type: String,
    },
    nameField: {
      type: String,
    },
    targetType: {
      type: String,
    },
    targetTypePlural: {
      type: String,
    },
    query: {
      type: String,
    },
    required: {
      type: Boolean,
      default: false,
    },
  },
  mounted() {
    this.fetch();
  },
  methods: {
    async fetch() {
      this.isLoading = true;
      axios
        .post("/graphql", {
          query: this.query,
          variables: { page: 1 },
        })
        .then((result) => {
          if (result.data.errors) {
            // TODO
            console.error(result.data.errors);
            return;
          }
          const data = result.data.data;
          this.$set(this, "options", data[this.targetTypePlural].data);
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
};
</script>
