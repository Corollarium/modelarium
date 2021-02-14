<template>
  <div class="modelarium-show {|lowerName|}-show">
    <div {|{containerAtts}|}>{|{ form }|}</div>

    <div class="modelarium-show__buttons {|lowerName|}-show__buttons">
      {|{buttonEdit}|} {|{buttonDelete}|}
    </div>
  </div>
</template>

<script>
import {|options.axios.method|} from "{|options.axios.importFile|}";
import queryItem from "raw-loader!./queryItem.graphql";
import mutationDelete from "raw-loader!./mutationDelete.graphql";
import model from "./model";

export default {
  data() {
    return {
      model: model,
      queryItem: queryItem,
      mutationDelete: mutationDelete,
      {|{extraData}|}
    };
  },

  created() {
    this.load();
  },

  methods: {
    load() {
      this.get(this.$route.params.{|keyAttribute|});
    },

    can(ability) {
      return this.model.can.find((i) => i.ability === ability && i.value);
    },

    cleanIdentifier(identifier) {
      {|{options.cleanIdentifierBody}|}
    },

    get(id) {
      return {|options.axios.method|}
        .post("/graphql", {
          query: this.queryItem,
          variables: { {|keyAttribute|}: this.cleanIdentifier(id) },
        })
        .then((result) => {
          if (result.data.errors) {
            // TODO
            console.error(result.data.errors);
            return;
          }
          const data = result.data.data;
          if (data.{|lowerName|} === null) {
            this.notFound404();
            return;
          }
          this.$set(this, "model", data.{|lowerName|});
        });
    },

    notFound404() {
       window.location = '/404';
    },

    remove() {
      if (!window.confirm("Really delete?")) {
        return;
      }
      return {|options.axios.method|}
        .post("/graphql", {
          query: this.mutationDelete,
          variables: { id: this.model.id },
        })
        .then((result) => {
          if (result.data.errors) {
            // TODO
            console.error(result.data.errors);
            return;
          }
          this.$router.push("/{|lowerName|}/");
        });
    },
  },
};
</script>
<style></style>
