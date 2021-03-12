<template>
  <div class="modelarium-show {|lowerName|}-show">
    <div {|{containerAtts}|}>{|{ form }|}</div>

    <div class="modelarium-show__buttons {|lowerName|}-show__buttons">
      {|{buttonEdit}|} {|{buttonDelete}|}
    </div>
  </div>
</template>

<script>
import queryItem from "raw-loader!./queryItem.graphql";
import mutationDelete from "raw-loader!./mutationDelete.graphql";
import crud from "./crud";
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

  mixins: [ crud ],

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

    notFound404() {
       window.location = '/404';
    },
  },
};
</script>
<style></style>
