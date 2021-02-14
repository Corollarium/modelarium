import {| StudlyName |}Card from "./{|StudlyName|}Card";
import axios from 'axios';
import listQuery from 'raw-loader!./list.graphql';

export default {
  data() {
    return {
      type: "{|lowerName|}",
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

  components: { {| StudlyName |} Card: {| StudlyName |} Card },

created() {
  if (this.$route) {
    if (this.$route.query.page && this.$route.query.page > 1) {
      this.pagination.currentPage = this.$route.query.page;
    }
  }
  this.index(this.pagination.currentPage);
},

watch: {
  "pagination.currentPage": {
    handler(newVal, oldVal) {
      if (this.$route) {
        if (this.$route.query.page != newVal) {
          // TODO this.$router.push(this._indexURL(newVal));
          this.index(newVal);
        }
      }
    }
  },
},

methods: {
  index(page) {
    return axios.post(
      '/graphql',
      {
        query: listQuery,
        variables: { page },
      }
    ).then((result) => {
      if (result.data.errors) {
        // TODO
        console.error(result.data.errors);
        return;
      }
      const data = result.data.data;
      this.$set(this, 'list', data.posts.data);
      this.$set(this, 'pagination', data.posts.paginatorInfo);
    });
  }
}
};
