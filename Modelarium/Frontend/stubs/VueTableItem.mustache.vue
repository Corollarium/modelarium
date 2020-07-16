<template>
  <tr>
    {|#props|}
    <td>{{ {|name|} }}</td>
    {|/props|}
  </tr>
</template>
<script>
export default {
  props: [
    {|#props|}
    {
      name: {|name|},
      {|#type|}type: {|.|},{|/required|}
      {|#required|}required: true,{|/required|}
    {|/props|}
  ],

  computed: {
    link() {
      return "/{|lowerName|}/" + this.id;
    },
  },
};
</script>
<style></style>
