<template>
  <form class="modelarium-form" method="POST" @submit.prevent="create">
    {|{ form }|} {|{ buttonSubmit }|}
  </form>
</template>

<script>
import axios from "axios";
import mutationCreate from "raw-loader!./mutationCreate.graphql";
import itemQuery from "raw-loader!./queryItem.graphql";
import model from "./model";

export default {
  data() {
    return {
      model: model,
    };
  },

  created() {
    if (this.$route.params.id) {
      this.get(this.$route.params.id);
    }
  },

  methods: {
    get(id) {
      axios
        .post("/graphql", {
          query: itemQuery,
          variables: { id },
        })
        .then((result) => {
          if (result.data.errors) {
            // TODO
            console.error(result.data.errors);
            return;
          }
          const data = result.data.data;
          this.$set(this, "model", data.post);
        });
    },

    create() {
      axios
        .post("/graphql", {
          query: mutationCreate,
          variables: this.model,
        })
        .then((result) => {
          if (result.data.errors) {
            // TODO
            console.error(result.data.errors);
            return;
          }
          const data = result.data.data;
          this.$set(this, "model", data.post);
          // TODO: route to '/{|lowerName|}/' + this.model.id
        });
    },

    changedFile(name, event) {
      // TODO
    },
  },
};
</script>
<style></style>
