<template>
  <tr class="modelarium-list__row">
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
      name: "{|name|}",
      {|#type|}type: {|.|},{|/type|}
      {|#required|}required: true,{|/required|}
    },
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
