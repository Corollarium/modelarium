<template>
  <form class="modelarium-form" method="POST">
    {|{ form }|} {|{ submitButton }|}
  </form>
</template>

<script>
import axios from "axios";
import mutationCreate from "raw-loader!./mutationCreate.graphql";

export default {
  data() {
    return {
      model: {},
    };
  },

  methods: {
    create() {
      axios
        .post("/graphql", {
          query: mutationCreate,
          variables: this.model,
        })
        .then((result) => {
          if (result.data.errors) {
            // TODO
            console.error(result.data.errors);
            return;
          }
          const data = result.data.data;
          this.$set(this, "model", data.post);
          // TODO: route to '/{|lowerName|}/' + this.model.id
        });
    },

    changedFile(name, event) {
      // TODO
    },
  },
};
</script>
<style></style>
