<template>
  <tr class="modelarium-list__row">
    {|#tableItemFields|}
    <td>{{ {|.|} }}</td>
    {|/tableItemFields|}
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
