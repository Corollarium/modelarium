<template>
  <div>
    {|{ form }|}
  </div>
</template>

<script>
import axios from "axios";
import itemQuery from "raw-loader!./queryItem.graphql";
import model from "./model";

export default {
  data() {
    return {
      model: model,
    };
  },

  created() {
    this.get(this.$route.params.id);
  },

  methods: {
    get(id) {
      axios
        .post("/graphql", {
          query: itemQuery,
          variables: { id },
        })
        .then((result) => {
          if (result.data.errors) {
            // TODO
            console.error(result.data.errors);
            return;
          }
          const data = result.data.data;
          this.$set(this, "model", data.post);
        });
    },
  },
};
</script>
<style></style>
