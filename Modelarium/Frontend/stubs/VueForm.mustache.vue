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
import itemQuery from "raw-loader!./queryItem.graphql";
import model from "./model";
{|{imports}|}

export default {
  data() {
    return {
      model: model,
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

      return {|options.axios.method|}
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
