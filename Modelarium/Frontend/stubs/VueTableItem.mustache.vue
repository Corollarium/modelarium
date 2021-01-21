<template>
  <tr class="modelarium-list__row {|lowerName|}-list__row">
    {|#tableItemFields|}
    <td>{|{.}|}</td>
    {|/tableItemFields|}
    <slot name="extraColumns" :props="$props"></slot>
  </tr>
</template>
<script>
{|{script}|}
</script>
<style></style>
