<template>
  <div class="modelarium-show {|lowerName|}-show">
    <div {|{containerAtts}|}>{|{ form }|}</div>

    <div class="modelarium-show__buttons {|lowerName|}-show__buttons">
      {|{buttonEdit}|} {|{buttonDelete}|}
    </div>
  </div>
</template>

<script>
import axios from "axios";
import itemQuery from "raw-loader!./queryItem.graphql";
import mutationDelete from "raw-loader!./mutationDelete.graphql";
import model from "./model";

export default {
  data() {
    return {
      model: model,
      can: {
        edit: true,
        delete: true,
      },
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

    cleanIdentifier(identifier) {
      return identifier.replace('_', ' ');
    },

    get(id) {
      axios
        .post("/graphql", {
          query: itemQuery,
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
      axios
        .post("/graphql", {
          query: mutationDelete,
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
