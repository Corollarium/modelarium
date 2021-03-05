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
import {|options.vue.axios.method|} from "{|options.vue.axios.importFile|}";
import mutationCreate from "raw-loader!./mutationCreate.graphql";
import mutationUpsert from "raw-loader!./mutationUpsert.graphql";
import queryItem from "raw-loader!./queryItem.graphql";
import model from "./model";
{|{imports}|}

export default {
  data() {
    return {
      model: model,
      queryItem: queryItem,
      mutationCreate: mutationCreate,
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
      return {|options.vue.axios.method|}
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
      if (this.model.id) {
        this.update();
      }
      else {
        this.create();
      }
    },

    update() {
      let postData = { {|updateGraphqlVariables|} };

      return {|options.vue.axios.method|}
        .post("/graphql", {
          query: this.mutationUpsert,
          variables: { input: postData },
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

    create() {
      let postData = { {|createGraphqlVariables|} };

      return {|options.vue.axios.method|}
        .post("/graphql", {
          query: this.mutationCreate,
          variables: { input: postData },
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
