<template>
  <div class="{|lowerName|}-card">
    <router-link :to="link">
      {{ {| titleField |} }}
    </router-link>
  </div>
</template>
<script>
export default {
  props: {|{propsCode}|},

  computed: {
    link() {
      return "/{| lowerName |}/" + this.id;
    },
  },
};
</script>
<style></style>
