import {|options.vue.axios.method|} from "{|options.vue.axios.importFile|}";

export default {
    methods: {
        cleanIdentifier(identifier) {
            {|{options.vue.cleanIdentifierBody}|}
        },

        escapeIdentifier(identifier) { 
            {|{options.vue.escapeIdentifierBody}|} 
        },
      
        get(id) {
            return {| options.vue.axios.method |}
                .post("/graphql", {
                    query: this.queryItem,
                    variables: { {| keyAttribute |}: this.cleanIdentifier(id) },
                })
                .then((result) => {
                    if (result.data.errors) {
                        // TODO
                        console.error(result.data.errors);
                        return;
                    }
                    const data = result.data.data;
                    this.$set(this, "model", data.{|lowerName|});
                });
        },

        save() {
            if (this.model.id) {
                this.update();
            }
            else {
                this.create();
            }
        },

        create() {
            let postData = { 
                {| createGraphqlVariables |} 
            };

            return {| options.vue.axios.method |}.post("/graphql", {
                query: this.mutationCreate,
                variables: { input: postData },
            })
            .then((result) => {
                if (result.data.errors) {
                    // TODO
                    console.error("errors", result.data.errors);
                    return;
                }
                const data = result.data.data;
                this.$router.push("/{|routeBase|}/" + this.escapeIdentifier(data.create{| studlyName |}.{| keyAttribute |}));
            });
        },

        update() {
            let postData = { 
                id: this.model.id,
                {| updateGraphqlVariables |} 
            };

            return {| options.vue.axios.method |}.post("/graphql", {
                query: this.mutationUpdate,
                variables: { input: postData },
            })
            .then((result) => {
                if (result.data.errors) {
                    // TODO
                    console.error("errors", result.data.errors);
                    return;
                }
                const data = result.data.data;
                this.$router.push("/{|routeBase|}/" + this.escapeIdentifier(data.update{| studlyName |}.{| keyAttribute |}));
            });
        },

        remove(id) {
            if (!window.confirm('{|options.frontend.messages.reallyDelete|}')) {
              return;
            }
            return {|options.vue.axios.method|}
              .post("/graphql", {
                query: this.mutationDelete,
                variables: { id: id },
              })
              .then((result) => {
                if (result.data.errors) {
                  // TODO
                  console.error(result.data.errors);
                  return;
                }
                this.$router.push("/{|routeBase|}/");
              });
          },
    }       
};