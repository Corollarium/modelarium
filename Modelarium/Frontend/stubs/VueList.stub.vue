<template>
  <main class="formularium-list">
    <h1 class="formularium-list__title">Post</h1>

    <div class="formularium-list__header">
      <router-link to="/post/edit">
        <i class="fa fa-plus"></i>
        Add your post
      </router-link>
    </div>

    <div class="formularium-list__list" v-if="list.length">
      <Card v-for="l in list" :key="l.id" v-bind="l"> </Card>
      <hr />

      <div v-html="pagination.html"></div>
    </div>
    <div class="formularium-list__list" v-else>
      Nothing found.
    </div>
  </main>
</template>

<script>
import {{StudlyName}}Card from "./{{StudlyName}}Card";
import axios from "axios";

export default {
  data() {
    return {
      type: "{{lowerName}}",
      list: [],
      pagination: {
        currentPage: 1,
        lastPage: 1,
        perPage: 20,
        lastPage: 1,
        html: "",
      },
    };
  },

  components: { Card: {{StudlyName}}Card },

  created() {
    if (this.$route) {
      if (this.$route.query.page && this.$route.query.page > 1) {
        this.pagination.current_page = this.$route.query.page;
      }
    }

    this.index(1);
  },

  watch: {
    "pagination.current_page": function (newVal, oldVal) {
      if (this.$route) {
        if (this.$route.query.page != newVal) {
          this.$router.push(this._indexURL(newVal));
          this.index(newVal);
        }
      }
    },
  },

  methods: {
    index(page) {
      axios({
        url: "/graphql",
        method: "post",
        data: {
          query: `
{
  {{lowerName}}(page: ${page}) {
  	data {
      id,
      title
    },

    paginatorInfo {
      currentPage,
      perPage,
      total,
      lastPage
    }
  },

}
                `,
        },
      }).then((result) => {
        const data = result.data.data;
        this.$set(this, "list", data.posts.data);
        this.$set(this, "pagination", data.posts.paginatorInfo);
      });
    },
  },
};
</script>
<style></style>
