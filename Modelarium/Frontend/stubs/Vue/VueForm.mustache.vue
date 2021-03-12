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
import mutationUpdate from "raw-loader!./mutationUpdate.graphql";
import queryItem from "raw-loader!./queryItem.graphql";
import crud from "./crud";
import model from "./model";
{|{imports}|}

export default {
  data() {
    return {
      model: model,
      queryItem: queryItem,
      mutationCreate: mutationCreate,
      mutationUpdate: mutationUpdate,
      {|{extraData}|}
    };
  },

  mixins: [ crud ],

  created() {
    if (this.$route.params.{|keyAttribute|}) {
      this.get(this.$route.params.{|keyAttribute|});
    }
  },

  methods: {
    changedFile(name, event) {
      // TODO
    },
  },
};
</script>
<style></style>
