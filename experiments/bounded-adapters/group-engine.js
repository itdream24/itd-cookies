/* Original GPL-2.0-or-later. Explicit classic dependency groups, no DOM interception. */
(function(root) {
    'use strict';
    class Runner {
        constructor(groups, execute) {
            this.execute=execute;
            this.choice={analytics:false,marketing:false};
            this.groups=groups.map(group=>({...group,state:'DENIED',errors:[],states:{},promises:new Map()}));
            const groupIds=new Set();
            for(const group of this.groups) {
                let error=group.issue || null;
                if(groupIds.has(group.id)||!/^[a-z][a-z0-9-]*$/.test(group.id)||!['analytics','marketing'].includes(group.category)) error='INVALID_GROUP';
                groupIds.add(group.id);
                if(!Array.isArray(group.nodes)){group.nodes=[];error='INVALID_NODES';}
                const ids=new Set();
                for(const node of group.nodes) {if(!node||typeof node.id!=='string'||!/^[a-z][a-z0-9:-]*$/.test(node.id)||!Array.isArray(node.deps)){error='INVALID_NODE';break;}if(ids.has(node.id))error='DUPLICATE_NODE';ids.add(node.id);group.states[node.id]='PENDING';}
                const visiting=new Set(),done=new Set();
                const visit=id=>{
                    if(visiting.has(id)) {error='CYCLE';return;}
                    if(done.has(id))return;
                    const node=group.nodes.find(n=>n.id===id);
                    if(!node){error='MISSING_DEPENDENCY';return;}
                    visiting.add(id);for(const dependency of node.deps)visit(dependency);visiting.delete(id);done.add(id);
                };
                if(!error)for(const node of group.nodes) visit(node.id);
                if(error){group.state='FAILED';group.errors.push(error);}
            }
        }
        async runNode(group,node) {
            if(group.promises.has(node.id))return group.promises.get(node.id);
            const work=(async()=>{
                for(const id of node.deps) {
                    const dependency=group.nodes.find(n=>n.id===id);
                    if(!await this.runNode(group,dependency)){group.states[node.id]='SKIPPED_DEPENDENCY';return false;}
                }
                if(!this.choice[group.category]){group.states[node.id]='DENIED';return false;}
                group.states[node.id]='RUNNING';
                try {await this.execute(node,group);group.states[node.id]='DONE';return true;}
                catch(error){group.states[node.id]='FAILED';group.errors.push(node.id+':'+error.message);return false;}
            })();
            group.promises.set(node.id,work);return work;
        }
        async grant(choice) {
            this.choice={analytics:choice.analytics===true,marketing:choice.marketing===true};
            await Promise.all(this.groups.map(async group=>{
                if(['FAILED','DONE'].includes(group.state)||!this.choice[group.category])return;
                group.state='RUNNING';
                const results=await Promise.all(group.nodes.map(node=>this.runNode(group,node)));
                group.state=results.every(Boolean)?'DONE':(group.errors.length?'FAILED':'DENIED');
            }));
        }
        snapshot(){return this.groups.map(g=>({id:g.id,category:g.category,state:g.state,states:g.states,errors:g.errors,resources:g.resources||[]}));}
    }
    root.ITDBoundedRunner=Runner;
})(typeof window==='undefined' ? globalThis : window);
