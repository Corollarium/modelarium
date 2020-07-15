<template>
  <div>
    <router-link :to="link">
      {{ id }}
      {{ title }}
      // TODO
    </router-link>
  </div>
</template>
<script>
import mutationCreate from "raw-loader!./mutationCreate.graphql";

export default {
  props: {
    id: {
      type: [Number, String],
      required: true,
    },
    // TODO
  },

  computed: {
    link() {
      return "/{{nameLower}}/" + this.id;
    },
  },
};
</script>
<style></style>
