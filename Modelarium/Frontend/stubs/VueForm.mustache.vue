<template>
  <form class="modelarium-form" method="POST" @submit.prevent="save">
    {|{ form }|} {|{ buttonSubmit }|}
  </form>
</template>

<script>
import axios from "axios";
import mutationUpsert from "raw-loader!./mutationUpsert.graphql";
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

    save() {
      let postData = { ...this.model };
      let query;

      axios
        .post("/graphql", {
          query: mutationUpsert,
          variables: { "{|lowerName|}": postData },
        })
        .then((result) => {
          if (result.data.errors) {
            // TODO
            console.error("errors", result.data.errors);
            return;
          }
          const data = result.data.data;
          this.$router.push("/{|lowerName|}/" + data.upsert{|studlyName|}.id);
        });
    },

    changedFile(name, event) {
      // TODO
    },
  },
};
</script>
<style></style>
