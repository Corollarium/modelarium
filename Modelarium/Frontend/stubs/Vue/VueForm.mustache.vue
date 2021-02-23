<template>
  <form
    class="modelarium-form {|lowerName|}-form"
    method="POST"
    @submit.prevent="save"
  >
    {|{ form }|} {|{ buttonSubmit }|}
  </form>
</template>

<script>
import {|options.axios.method|} from "{|options.axios.importFile|}";
import mutationUpsert from "raw-loader!./mutationUpsert.graphql";
import queryItem from "raw-loader!./queryItem.graphql";
import model from "./model";
{|{imports}|}

export default {
  data() {
    return {
      model: model,
      queryItem: queryItem,
      mutationUpsert: mutationUpsert,
      {|{extraData}|}
    };
  },

  created() {
    if (this.$route.params.id) {
      this.get(this.$route.params.id);
    }
  },

  methods: {
    get(id) {
      return {|options.axios.method|}
        .post("/graphql", {
          query: this.queryItem,
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

      return {|options.axios.method|}
        .post("/graphql", {
          query: this.mutationUpsert,
          variables: { "{|lowerName|}": postData },
        })
        .then((result) => {
          if (result.data.errors) {
            // TODO
            console.error("errors", result.data.errors);
            return;
          }
          const data = result.data.data;
          this.$router.push("/{|routeBase|}/" + data.upsert{|studlyName|}.id);
        });
    },

    changedFile(name, event) {
      // TODO
    },
  },
};
</script>
<style></style>
