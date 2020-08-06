<template>
  <tr class="modelarium-list__row">
    {|#props|}
    <td>{{ {|name|} }}</td>
    {|/props|}
  </tr>
</template>
<script>
export default {
  props: {|{propsCode}|},

  computed: {
    link() {
      return "/{|lowerName|}/" + this.id;
    },
  },
};
</script>
<style></style>
